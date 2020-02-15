<?php
/** @var $fontSize */
/** @var $lineHeight */
/** @var $textIndent */
/** @var $width */
/* @var $extra bool */
/* @var $this \yii\web\View */
/* @var $title string */
/* @var $content mixed|string */
/* @var $scoreBoxWidth mixed|string */
/* @var $scoreBoxHeight mixed|string */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>blank</title>
    <style>
        html, body, div, span, applet, object, iframe,
        h1, h2, h3, h4, h5, h6, p, blockquote, pre,
        a, abbr, acronym, address, big, cite, code,
        del, dfn, em, img, ins, kbd, q, s, samp,
        small, strike, strong, sub, sup, tt, var,
        b, u, i, center,
        dl, dt, dd, ol, ul, li,
        fieldset, form, label, legend,
        table, caption, tbody, tfoot, thead, tr, th, td,
        article, aside, canvas, details, embed,
        figure, figcaption, footer, header, hgroup,
        menu, nav, output, ruby, section, summary,
        time, mark, audio, video {
            margin: 0;
            padding: 0;
            border: 0;
            /*font-family: "Menlo", "Monaco", "Consolas", "Liberation Mono", "Courier New", "monospace", "sans-serif";*/
            font-family:  "Segoe UI", Arial, "Microsoft YaHei", sans-serif;
            vertical-align: baseline;
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

        /* HTML5 display-role reset for older browsers */
        article, aside, details, figcaption, figure,
        footer, header, hgroup, menu, nav, section {
            display: block;
        }

        ol, ul {
            list-style: none;
        }

        pre {
            font-size: <?=$fontSize;?>px;
            line-height: <?=$lineHeight;?>px;

            outline: none;
            overflow: unset;
            word-wrap: break-word;

            white-space: pre-wrap;
            word-break: break-all;
            margin-bottom: 0;
            position: relative;
        }

        .area {
            padding-left: 10px;
            width: <?=$width?>px;
            margin-top: 0;
            margin-bottom: 12px;
        }
        .score-box {
            width: <?=$scoreBoxWidth?>px;
            height: <?=$scoreBoxHeight?>px;
            margin-right: 44px;
            position: relative;
            float: left;
        }

        .subjective-item-title {
            font-size: <?=$fontSize;?>px;
            color: #000000;
            left: 10px;
            top: 0;
            line-height: <?=$lineHeight;?>px;
            float: left;
        }
    </style>
</head>
<body>
<div class="area">
    <?php if ($scoreBoxHeight && $scoreBoxWidth): ?>
        <div class="score-box"></div>
    <?php endif;?>
    <?php if (!$extra): ?>
        <span class="subjective-item-title"><?= $title; ?></span>
    <?php endif;?>
    <pre><?= $content ?></pre>
</div>
</body>
</html>