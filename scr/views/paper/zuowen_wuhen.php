<?php
/** @var string $title */
/** @var $size */
/** @var $width */
/** @var $allNum */
/** @var $lineCount */
/** @var $frontWordsNum */
/** @var $fontSize */
/** @var $lineHeight */
/** @var $textIndent */
/** @var $color */

use ydb\card\assets\PapaerCardAsset;

$bundle = PapaerCardAsset::register($this);
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
            font-family: "Segoe UI", Arial, "Microsoft YaHei", sans-serif;
        }

        html {
            overflow-x:hidden;
            overflow-y:hidden;
        }

        body {
            background: transparent;
            overflow-x:hidden;
            overflow-y:hidden;
        }

        article, aside, details, figcaption, figure,
        footer, header, hgroup, menu, nav, section {
            display: block;
        }
        div {
            margin:0;
            padding:0;
            border: none;
        }
        body {
            font-size: 0;
            width: <?=$width?>px;
        }
        ol, ul {
            list-style: none;
        }
        pre {
            font-size: <?=$fontSize;?>px;
            line-height: <?=$lineHeight;?>px;
        }

        .title {
            display: block;
            line-height: 1;
            font-size: 36px;
            color: #000;
            margin-bottom: 20px;
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
<div>
    <?php if ($frontWordsNum == 0 && !empty($title)):?>
        <span class="title"><?=$title?></span>
    <?php endif;?>
    <div class="box-image">
        <?php /** @var int $lineStart */
        for ($line = $lineStart; $line < $lineCount; $line++): ?>
            <img class="box" src="<?=$bundle->baseUrl?>/images/zuowen_row_<?=$size?>_<?=$count?>_<?=$color?>.svg">
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
                <p style="font-size: 16px; line-height: 16px; text-align: right; color: #000; margin-top: -16px; margin-bottom: 0; width: <?=$boxWidth?>px;"><?=$num * 200;?></p>
            <?php endif;?>
            <?php if($needTotalLine && $allNum < 200):?>
                <?php
                $pos = $count - $lineEnd % $allNum;
                $boxWidth = ($size == 'small' ? 90 : 104) * $pos;
                ?>
                <p style="font-size: 16px; line-height: 16px; text-align: right; color: #000; margin-top: -16px; margin-bottom: 0; width: <?=$boxWidth?>px;"><?=$allNum?></p>
            <?php endif; ?>
        <?php endfor; ?>
    </div>
</div>
</body>
</html>