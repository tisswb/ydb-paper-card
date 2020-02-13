<?php
/**
 * Created by MSchool.
 * User: Tisswb
 * Date: 2018-07-13
 * Time: 9:53
 */

namespace ydb\card\components;

use ydb\card\CardImage;
use yii\base\BaseObject;

/**
 * Class SecretTag
 * @package card\components
 */
class SecretTag extends BaseObject
{
    /**
     * @param CardImage $card
     */
    public static function draw(&$card)
    {
        $headers = Header::createArray($card);
        // disabled for ocr
        if (0 && isset($headers['showSecretTag']) && $headers['showSecretTag'] == ACTIVE_YES) {
            $svg = $card->imageDir . '/secret_tag.svg';
            $card->composeSVG(
                $svg,
                $card->left + $card->contentWidth - 254,
                $card->top
            );
        }
    }
}
