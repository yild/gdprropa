<?php

/*
 -------------------------------------------------------------------------
 GDPR Records of Processing Activities plugin for GLPI
 Copyright © 2020-2025 by Yild.

 https://github.com/yild/gdprropa
 -------------------------------------------------------------------------

 LICENSE

 This file is part of GDPR Records of Processing Activities.

 GDPR Records of Processing Activities is free software; you can
 redistribute it and/or modify it under the terms of the
 GNU General Public License as published by the Free Software
 Foundation; either version 3 of the License, or (at your option)
 any later version.

 GDPR Records of Processing Activities is distributed in the hope that
 it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 See the GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with GDPR Records of Processing Activities.
 If not, see <https://www.gnu.org/licenses/>.

 Based on DPO Register plugin, by Karhel Tmarr.

 --------------------------------------------------------------------------

  @package   gdprropa
  @author    Yild
  @copyright Copyright © 2020-2025 by Yild
  @license   GPLv3+
             https://www.gnu.org/licenses/gpl.txt
  @link      https://github.com/yild/gdprropa
  @since     1.0.0
 --------------------------------------------------------------------------
 */

namespace GlpiPlugin\Gdprropa;

use Html;
use Session;

include("../../../inc/includes.php");

if (!isset($_GET['id'])) {
    $_GET['id'] = "";
}

$record = new Record();

if (isset($_POST['add'])) {
    $record->check(-1, CREATE, $_POST);
    $record->add($_POST);
    Html::back();
} elseif (isset($_POST['update'])) {
    $record->check($_POST['id'], UPDATE);
    $record->update($_POST);
    Html::back();
} elseif (isset($_POST['delete'])) {
    $record->check($_POST['id'], DELETE);
    $record->delete($_POST);
    $record->redirectToList();
} elseif (isset($_POST['purge'])) {
    $record->check($_POST['id'], PURGE);
    $record->purge($_POST);
    $record->redirectToList();
} else {
    $record->checkGlobal(READ);

    if (Session::getCurrentInterface() == 'central') {
        Html::header(Record::getTypeName(), $_SERVER['PHP_SELF'], 'management', Menu::class);
    } else {
        Html::helpHeader(Record::getTypeName(), $_SERVER['PHP_SELF']);
    }

    $record->display($_GET);

    if (Session::getCurrentInterface() == 'central') {
        Html::footer();
    } else {
        Html::helpFooter();
    }
}
