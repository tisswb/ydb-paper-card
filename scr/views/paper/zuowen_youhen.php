<?php
/** @var string $title */
/** @var $size */
/** @var $width */
/** @var $allNum */
/** @var $lineCount */
/** @var $minLine */
/** @var $min */
/** @var $linePos */
/** @var $fontSize */
/** @var $lineHeight */
/** @var $textIndent */
/** @var $frontWordsNum */
/** @var $lineStart */
/** @var $color */
/* @var $scoreBoxWidth mixed|string */
/* @var $scoreBoxHeight mixed|string */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>blank</title>
    <style>
        *, html, body, div, p, img, pre {
            margin: 0;
            padding: 0;
            border: 0;
            /*font-family: "Menlo", "Monaco", "Consolas", "Liberation Mono", "Courier New", "monospace", "sans-serif";*/
            font-family: "Segoe UI", Arial, "Microsoft YaHei", sans-serif;
            /*vertical-align: baseline;*/
        }

        /* HTML5 display-role reset for older browsers */
        article, aside, details, figcaption, figure,
        footer, header, hgroup, menu, nav, section {
            display: block;
        }

        div {
            margin: 0;
            padding: 0;
            border: none;
        }

        html {
            overflow-x:hidden;
            overflow-y:hidden;
        }

        body {
            font-size: 0;
            width: <?=$width?>px;
            background: transparent;
            overflow-x:hidden;
            overflow-y:hidden;
        }

        ol, ul {
            list-style: none;
        }

        pre {
            font-size: <?=$fontSize;?>px;
            line-height: <?=$lineHeight;?>px;
        }
        .scoring-frame-two {
            width: <?=$scoreBoxWidth?>px;
            height: <?=$scoreBoxHeight?>px;
        }

        .scoring-frame {
            margin-right: 44px;
            position: relative;
        }
        .pull-left {
            float: left;
        }
        .composition-head h6 {
            font-size: <?=$fontSize;?>px;
            color: #000000;
            font-weight: 400;
            margin-bottom: 0;
            line-height: <?=$fontSize;?>px;
            height: 48px;
            position: relative;
            width: 100%;
            padding-left: 20px;
        }
        .composition-head {
            margin-bottom: 28px;
            position: relative;
            float: left;
        }
        .clearfix::after {
            display: table;
            content: "";
            clear: both;
        }

        .title {
            display: block;
            line-height: 1;
            font-size: 36px;
            color: #000;
            margin-bottom: 20px;
            float: left;
        }

        .box-image {
            width: <?=$width?>px;
            text-align: center;
        }
        .box {
            margin-bottom: <?=($size == 'small' ? 16 : 14)?>px;
        }
    </style>
</head>
<body>
<?php if ($frontWordsNum == 0):?>
    <div class="composition-head clearfix">
        <div class="scoring-frame pull-left scoring-frame-two"></div>
    </div>
    <?php if (!empty($title)): ?>
        <span class="title"><?=$title?></span>
    <?php endif;?>
<?php endif;?>
<div class="box-image">
    <?php for ($line = $lineStart; $line < $lineCount; $line++): ?>
        <img class="box" src="<?=Yii::getAlias('@staticUrl')?>/card_img/zuowen_row_<?=$size?>_<?=$count?>_<?=$color?>.svg">
        <?php
        $lineStart = $count * $line - $count + 1;
        $lineEnd = $count * $line;
        $needLine = ((int)($lineEnd / 200) - (int)($lineStart / 200) == 1);
    $needTotalLine = ((int)($lineEnd / $allNum) - (int)($lineStart / $allNum) == 1)
        ?>
        <?php if($needLine):?>
            <?php
            $pos = $count - $lineEnd % 200;
            $num = (int)($lineEnd / 200);
            $boxWidth = ($size == 'small' ? 90 : 104) * $pos;
            ?>
            <p style="font-size: 16px; line-height: 16px; text-align: right; color: #000; margin-top: -16px; margin-bottom: 0; width: <?=$boxWidth?>px;"><?=$num * 200?></p>
        <?php endif; ?>
        <?php if($needTotalLine && $allNum < 200):?>
            <?php
            $pos = $count - $lineEnd % $allNum;
            $boxWidth = ($size == 'small' ? 90 : 104) * $pos;
            ?>
            <p style="font-size: 16px; line-height: 16px; text-align: right; color: #000; margin-top: -16px; margin-bottom: 0; width: <?=$boxWidth?>px;"><?=$allNum?></p>
        <?php endif; ?>
    <?php endfor; ?>
</div>
</body>
</html>