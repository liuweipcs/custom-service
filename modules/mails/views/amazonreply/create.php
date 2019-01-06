<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\modules\mails\models\AmazonReply */

?>
<div class="popup-wrapper">

    <?php if(isset($subject))
    {
        echo $this->render('subject_form', [
            'model' => $model,
            'subject_id' => $subject_id,
            'receiver' => $receiver,
        ]);
    }
    else
    {
        echo $this->render('_form', [
            'model' => $model,
        ]);
    }
    ?>

</div>
