<?php

/**
 * @param PDO $oDbh
 * @param int $iPage
 * @return string
 */
function getHowManyPokemons(PDO $oDbh)
{
    $sSql = 'SELECT COUNT(*) FROM `pokemon` WHERE `active`=:active ORDER BY `position`';
    $oSth = $oDbh->prepare($sSql);

    $oSth->execute(
        array(
            'active' => 1,
        )
    );

    return $oSth->fetchColumn(0);
}

/**
 * @param PDO $oDbh
 * @param int $iPage
 * @return string
 */
function getPokemonsHTML(PDO $oDbh, int $iPage)
{
    $sHTML = '';
    $iFrom = ($iPage - 1) * 50;
    $sSql = '
        SELECT p.*, IF(dp.`drawn_date` IS NOT NULL, 1, 0) AS drawn
        FROM `pokemon` p
        LEFT JOIN `drawn_pokemon` dp
        ON p.`id` = dp.`id_pokemon`
        WHERE p.`active` = :active
        GROUP BY p.`id`
        ORDER BY p.`position`
        LIMIT ' . $iFrom . ', 50
    ';
    $oSth = $oDbh->prepare($sSql);

    $oSth->execute(
        array(
            'active' => 1,
        )
    );

    $aPokemons = $oSth->fetchAll();

    for ($i = 0; $i < count($aPokemons); $i++) {
        $sVariant = $aPokemons[$i]['variant'] != 'none' ? $aPokemons[$i]['variant'] : null;
        $sHumanVariant = $sVariant != null ? str_replace('-', ' ', $sVariant) : null;
        $sName = $aPokemons[$i]['name'];

        if ($sHumanVariant != null) {
            $sName .= ' ' . $sHumanVariant;
        }

        $iPosition = (int) $aPokemons[$i]['position'];
        $bDrawn = $aPokemons[$i]['drawn'] == '1';

        $iSpriteIndex = floor(($iPosition - 1) / 50);
        $iSpriteOffset = (($iPosition - 1) % 50) * 200;

        $sHTML .= '<div class="pokemon" title="' . $sName . '" style="';

        if ($bDrawn) {
            $sHTML .= 'background: none;">';

            $sFilePath = 'img/pokemons/drawn/' . $iPosition;

            if ($sVariant != null) {
                $sFilePath .= '-' . $sVariant;
            }

            $sThumbExtension = '.jpg';
            $sHdExtension = '.jpg';

            if (file_exists($sFilePath . '@2x.png')) {
                $sHdExtension = '.png';
            }

            if (file_exists($sFilePath . '@thumb.png')) {
                $sThumbExtension = '.png';
            }

            $sHTML .= '<img src="' . $sFilePath . '@thumb' . $sThumbExtension . '" data-jslghtbx="' . $sFilePath . '@2x' . $sHdExtension . '" data-jslghtbx-group="pokemons" data-pokemon-position="' . $iPosition . '" alt="' . $sName . '" />';
        } else {
            $sHTML .= 'background-image: url(\'img/pokemons/vanilla/' . $iSpriteIndex . '.png\'); background-position: 0 -' . $iSpriteOffset . 'px;">';
        }

        $sHTML .= '</div>';
    }

    return $sHTML;
}

/**
 * @param PDO $oDbh
 * @param int $iPosition
 * @return array
 */
function getVariants(PDO $oDbh, int $iPosition)
{
    $sSql = '
        SELECT dp.`variant`
        FROM `pokemon` p
        JOIN `drawn_pokemon` dp
        ON p.`id` = dp.`id_pokemon`
        AND dp.`variant` != \'none\'
        WHERE p.`position` = :position
        ORDER BY CASE
            WHEN LOWER(dp.`variant`) = \'Shiny\' THEN 0
            WHEN LOWER(dp.`variant`) = \'Male\' THEN 1
            WHEN LOWER(dp.`variant`) = \'Male-Shiny\' THEN 2
            WHEN LOWER(dp.`variant`) = \'Female\' THEN 3
            WHEN LOWER(dp.`variant`) = \'Female-Shiny\' THEN 4
            WHEN LOWER(dp.`variant`) = \'Alola\' THEN 5
            WHEN LOWER(dp.`variant`) = \'Alola-Shiny\' THEN 6
            WHEN LOWER(dp.`variant`) = \'Mega\' THEN 7
            WHEN LOWER(dp.`variant`) = \'Mega-Shiny\' THEN 8
            ELSE 9
        END, dp.`variant`
    ';
    $oSth = $oDbh->prepare($sSql);

    $oSth->execute(
        array(
            'position' => (int) $iPosition,
        )
    );

    $aVariants = $oSth->fetchAll();
    $aReturn = array();

    for ($i = 0; $i < count($aVariants); $i++) {
        $aReturn[] = $aVariants[$i]['variant'];
    }

    return $aReturn;
}