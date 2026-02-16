<?php
/**
 * تبويب المحولين للشكوى — يُعرض عبر AJAX داخل الشاشة الموحدة
 * يُحمّل محتوى index-legal-department عبر iframe لأنه معقد ومستقل
 */
use yii\helpers\Url;

$legalUrl = Url::to(['/contracts/contracts/index-legal-department']);
?>

<script>
$('#lh-badge-legal').text('<?= $dataCount ?>');
</script>

<div style="margin:-16px">
    <iframe id="lh-legal-iframe" src="<?= $legalUrl ?>" style="width:100%;border:none;min-height:700px;display:block" onload="this.style.height=this.contentWindow.document.body.scrollHeight+'px'"></iframe>
</div>

<script>
(function(){
    var iframe = document.getElementById('lh-legal-iframe');
    function resizeIframe() {
        try {
            iframe.style.height = iframe.contentWindow.document.body.scrollHeight + 'px';
        } catch(e) {}
    }
    iframe.addEventListener('load', resizeIframe);
    setInterval(resizeIframe, 2000);
})();
</script>
