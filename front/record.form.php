<?php
/*
 -------------------------------------------------------------------------
 GDPR Records of Processing Activities plugin for GLPI
 Copyright (C) 2020 by Yild.

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
 If not, see <http://www.gnu.org/licenses/>.

 Based on DPO Register plugin, by Karhel Tmarr.

 --------------------------------------------------------------------------

  @package   gdprropa
  @author    Yild
  @copyright Copyright (c) 2020 by Yild
  @license   GPLv3+
             http://www.gnu.org/licenses/gpl.txt
  @link      https://github.com/yild/gdprropa
  @since     2020
 --------------------------------------------------------------------------
 */

include("../../../inc/includes.php");

if (!isset($_GET['id'])) {
   $_GET['id'] = "";
}

$record = new PluginGdprropaRecord();

if (isset($_POST['add'])) {

   $record->check(-1, CREATE, $_POST);
   $record->add($_POST);
   Html::back();

} else if (isset($_POST['update'])) {

   $record->check($_POST['id'], UPDATE);
   $record->update($_POST);
   Html::back();

} else if (isset($_POST['delete'])) {

   $record->check($_POST['id'], DELETE);
   $record->delete($_POST);
   $record->redirectToList();

} else if (isset($_POST['purge'])) {

   $record->check($_POST['id'], PURGE);
   $record->purge($_POST);
   $record->redirectToList();

} else {

   $record->checkGlobal(READ);

   if (Session::getCurrentInterface() == 'central') {
      Html::header(PluginGdprropaRecord::getTypeName(0), '', 'management', 'plugingdprropamenu');
   } else {
      Html::helpHeader(PluginGdprropaRecord::getTypeName(0));
   }

   $record->display(['id' => $_GET['id']]);

   if (Session::getCurrentInterface() == 'central') {
      Html::footer();
   } else {
      Html::helpFooter();
   }

}
