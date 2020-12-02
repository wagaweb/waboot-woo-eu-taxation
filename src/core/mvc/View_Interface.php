<?php
/*
 * This file is part of WBF Framework: https://github.com/wagaweb/wbf
 *
 * @author WAGA Team <dev@waga.it>
 */

namespace WBWooEUT\core\mvc;

interface View_Interface{
	public function display($vars = []);
	public function get($vars = []);
}