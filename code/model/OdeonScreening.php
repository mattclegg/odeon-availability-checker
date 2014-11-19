<?php
/*  Copyright 2014  mattclegg  (email : cleggmatt@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class OdeonScreening extends DataObject {

	private static $db = array(
		'Title'=>'HTMLText',
		'Cost'=>'Currency',
		'Availability'=>'Int',
		'ScreeningTime'=>'SS_Datetime',
		'SessionURL'=>'varchar(2083)'
	);

	private static $has_one = array(
		'Film'=>'OdeonFilm',
		'Cinema'=>'OdeonCinema'
	);

	public function olderThen($hour = 1) {
		return round(abs(time() - strtotime($this->LastEdited))/3600) > $hour;
	}

	public function getGroupedByTitle() {
		return $this->obj('ScreeningTime')->Format('D jS F Y');
	}

	public function getGroupedByTime() {
		return $this->obj('ScreeningTime')->Time24();
	}

	public function Link() {
		return "https://www.odeon.co.uk/{$this->SessionURL}";
	}

	public function checkAgainstAPI(){
		//Resut has been recorded as 0 (never going to increase)
		if($this->Availability=="0") {
			return false;
			//Result is over an hour old (might have decreased)
		} elseif($this->olderThen()) {
			return true;
			//Just use cached version
		} else {
			return false;
		}
	}

	public static function get($callerClass = null, $filter = "", $sort = "", $join = "", $limit = null, $containerClass = 'DataList') {
		$get = parent::get($callerClass, $filter, $sort, $join, $limit, $containerClass);
		$now = SS_Datetime::now()->getValue();
		foreach($get as $x) {
			if($x->ScreeningTime < $now) {
				$get->removeByID($x->ID);
				$x->delete();
			}
		}
		return $get;
	}

} 