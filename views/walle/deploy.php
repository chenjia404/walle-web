<?php
/**
 * @var yii\web\View $this
 */
$this->title = yii::t('walle', 'deploying');
use \app\models\Task;
use yii\helpers\Url;
?>
<style>
    .status > span {
        float: left;
        font-size: 12px;
        width: 14%;
        text-align: right;
    }
    .btn-deploy {
        margin-left: 30px;
    }
    .btn-return {
        /*float: right;*/
        margin-left: 30px;
    }
</style>
<div class="box" style="height: 100%">
    <h4 class="box-title header smaller red">
            <i class="icon-map-marker"></i><?= \Yii::t('w', 'conf_level_' . $task->project['level']) ?>
            -
            <?= $task->project->name ?>
            ：
            <?= $task->title ?>
            （<?= $task->project->repo_mode . ':' . $task->branch ?> <?= yii::t('walle', 'version') ?><?= $task->commit_id ?>）
            <?php if (in_array($task->status, [Task::STATUS_PASS, Task::STATUS_FAILED])) { ?>
                <button type="submit" class="btn btn-primary btn-deploy" data-id="<?= $task->id ?>"><?= yii::t('walle', 'deploy') ?></button>
            <?php } ?>
            <a class="btn btn-success btn-return" href="<?= Url::to('@web/task/index') ?>"><?= yii::t('walle', 'return') ?></a>
    </h4>
    <div class="status">
        <span><i class="fa fa-circle-o text-yellow step-1"></i><?= yii::t('walle', 'process_detect') ?></span>
        <span><i class="fa fa-circle-o text-yellow step-2"></i><?= yii::t('walle', 'process_pre-deploy') ?></span>
        <span><i class="fa fa-circle-o text-yellow step-3"></i><?= yii::t('walle', 'process_checkout') ?></span>
        <span><i class="fa fa-circle-o text-yellow step-4"></i><?= yii::t('walle', 'process_post-deploy') ?></span>
        <span><i class="fa fa-circle-o text-yellow step-5"></i><?= yii::t('walle', 'process_rsync') ?></span>
        <span style="width: 28%"><i class="fa fa-circle-o text-yellow step-6"></i><?= yii::t('walle', 'process_update') ?></span>
    </div>
    <div style="clear:both"></div>
    <div class="progress progress-small progress-striped active">
        <div class="progress-bar progress-status progress-bar-success" style="width: <?= $task->status == Task::STATUS_DONE ? 100 : 0 ?>%;"></div>
    </div>

    <div class="alert alert-block alert-success result-success" style="<?= $task->status != Task::STATUS_DONE ? 'display: none' : '' ?>">
        <h4><i class="icon-thumbs-up"></i><?= yii::t('walle', 'done') ?></h4>
        <p><?= yii::t('walle', 'done praise') ?></p>

    </div>

    <div class="alert alert-block alert-danger result-failed" style="display: none">
        <h4><i class="icon-bell-alt"></i><?= yii::t('walle', 'error title') ?></h4>
        <span class="error-msg">
        </span>
        <br><br>
        <i class="icon-bullhorn"></i><span><?= yii::t('walle', 'error todo') ?></span>
    </div>


    <div class="timeline-items">
        <div class="timeline-item clearfix">
            <div class="widget-box transparent" style="border-left:0;margin-left:0;">
                <div class="widget-header widget-header-small">
                    <h5 class="smaller">
                        <span class="grey">部署日志</span>
                    </h5>

                    <span class="widget-toolbar no-border">
                        <i class="icon-time bigger-110"></i>
                    </span>

                    <span class="widget-toolbar">
                        <a href="#" data-action="reload">
                            <i class="icon-refresh"></i>
                        </a>

                        <a href="#" data-action="collapse">
                            <i class="icon-chevron-up"></i>
                        </a>
                    </span>
                </div>

                <div class="widget-body">
                    <div class="widget-main">
                        <?php foreach ($record as $item) { ?>
                            <span class="gray">[<?= date('Y-m-d H:i:s', $item['created_at']) ?></span>
                            <span class="<?= $item['status'] ? 'green' : 'red' ?>"><?=nl2br(str_replace("&&","&&\n",$item['command'])) ?><br /></span>
                            <span class="gray"><?= nl2br($item['memo'])?><br /></span>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script type="text/javascript">
    $(function() {
        if(window.Notification)
        {
            if(Notification.permission !== "granted")
            {
                alert("请点击左上角同意，才能接收发版推送");
                Notification.requestPermission().then(function(result) {
                    // result可能是是granted, denied, 或default.
                    if(result == "denied")
                    {
                        alert("您拒绝了通知");
                    }
                });
            }
        }

        $('.btn-deploy').click(function() {
            $this = $(this);
            $this.addClass('disabled');
            var task_id = $(this).data('id');
            var action = '';
            var detail = '';
            var timer;
            $.post("<?= Url::to('@web/walle/start-deploy') ?>", {taskId: task_id}, function(o) {
                action = o.code ? o.msg + ':' : '';
                if (o.code != 0) {
                    $('.progress-status').removeClass('progress-bar-success').addClass('progress-bar-danger');
                    $('.error-msg').text(action + detail);
                    $('.result-failed').show();
                    $this.removeClass('disabled');
                }
            });
            $('.progress-status').attr('aria-valuenow', 10).width('10%');
            $('.result-failed').hide();
            function getProcess() {
                $.get("<?= Url::to('@web/walle/get-process?taskId=') ?>" + task_id, function (o) {
                    var data = o.data;
                    if (0 != data.percent) {
                        $('.progress-status').attr('aria-valuenow', data.percent).width(data.percent + '%');
                    }
                    // 执行失败
                    if (0 == data.status) {
                        $('.step-' + data.step).removeClass('text-yellow').addClass('text-red');
                        $('.progress-status').removeClass('progress-bar-success').addClass('progress-bar-danger');
                        detail = o.msg + ':' + data.memo + '<br>' + data.command;
                        $('.error-msg').html(action + detail);
                        $('.result-failed').show();
                        $this.removeClass('disabled');
                        return;
                    } else {
                        $('.progress-status')
                            .removeClass('progress-bar-danger progress-bar-striped')
                            .addClass('progress-bar-success');
                    }
                    if (100 == data.percent) {
                        $('.progress-status').removeClass('progress-bar-striped').addClass('progress-bar-success');
                        $('.progress-status').parent().removeClass('progress-striped');
                        $('.result-success').show();

                        //后台状态，发一个通知
                        if(document.visibilityState === "hidden" && Notification.permission === "granted")
                        {
                            var notification = new Notification("发版成功", {
                                body: '<?= $task->project->name ?>：<?= $task->title ?>'
                            });
                        }
                    } else {
                        setTimeout(getProcess, 600);
                    }
                    for (var i = 1; i <= data.step; i++) {
                        $('.step-' + i).removeClass('text-yellow text-red')
                            .addClass('text-green progress-bar-striped')
                    }
                });
            }
            setTimeout(getProcess, 600);
        })

        var _hmt = _hmt || [];
        (function() {
            var hm = document.createElement("script");
            hm.src = "//hm.baidu.com/hm.js?5fc7354aff3dd67a6435818b8ef02b52";
            var s = document.getElementsByTagName("script")[0];
            s.parentNode.insertBefore(hm, s);
        })();
    })
</script>
