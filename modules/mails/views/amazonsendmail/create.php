
<script type="text/javascript" src="/js/jquery.form.js"></script>
<div id="popup-wrapper">

    <?= $this->render('_form', [
        'model' => $model,
        'id' => $id,
        'type' => $type,
    ]) ?>

</div>
