<?php

use yii\helpers\Html;

?>
<?php $this->beginPage() ?>
<style>
body {
  background: rgb(204,204,204); 
}
page[size="A4"] {
  background: white;
  height: 37cm;
  display: block;
  margin: 0 auto;
  margin-bottom: 0.5cm;
  box-shadow: 0 0 0.5cm rgba(0,0,0,0.5);
}
@media print {
  body, page[size="A4"] {
    margin: 0;
    box-shadow: 0;
  }
}
</style>
<html dir="rtl" lang="ar">
<?= Html::csrfMetaTags() ?>
<?php $this->head() ?>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">
    <title>Print Template</title>
    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="https://cdn.rtlcss.com/bootstrap/v4.5.3/css/bootstrap.min.css"
        integrity="sha384-JvExCACAZcHNJEc7156QaHXTnQL3hQBixvj5RV5buE7vgnNEzzskDtx9NQ4p6BJe" crossorigin="anonymous">
    <!-- Custom styles for this template -->
    <!-- jquery cdn -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <!-- jquery cdn -->
    <script src="/js/Tafqeet.js"></script>
</head>

<body>
    <?php $this->beginBody() ?>
    <?= $this->render('content.php', ['content' => $content]) ?>
    <?php $this->endBody() ?>
</body>

</html>
<?php $this->endPage() ?>