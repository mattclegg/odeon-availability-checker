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

use Sunra\PhpSimple\HtmlDomParser;

class OdeonCinema extends DataObject {

	private static $db = array(
		'Title'=>'Text',
		'Address'=>'Text',
		'lat'=>'Text',
		'lng'=>'Text'
	);

	private static $has_many = array(
		'Screenings'=>'OdeonScreening'
	);

	public function Link() {
		return OdeonPage::get_one("OdeonPage")->Link("check/{$this->ID}");
	}

	public function getCurrentFilms() {

		$r = new ArrayList();

		//$RestfulService = new RestfulService("http://www.odeon.co.uk/api/uk/v2/cinemas/cinema/{$this->ID}/filmswithdetails.json");

		$RestfulService = new RestfulService("http://www.odeon.co.uk/api/uk/v2/cinemas/cinema/{$this->ID}/", 259200);

		$Response = $RestfulService->request("filmswithdetails.json");
		if (!$Response->isError()) {

			$films = Convert::json2array($Response->getBody());
			foreach ($films as $film) {

				$OdeonFilm = OdeonFilm::get_by_id('OdeonFilm', (int)$film['masterId']);
				if (!$OdeonFilm) {
					$OdeonFilm = new OdeonFilm();
					$OdeonFilm->ID = (int)$film['masterId'];
					$OdeonFilm->Title = Convert::raw2sql($film['title']);
					if (isset($film['media']['imageUrl400'])) {
						$OdeonFilm->imageUrlSmall = Convert::raw2sql($film['media']['imageUrl400']);
					}
					if (isset($film['casts'])) {
						$OdeonFilm->Content = Convert::raw2sql($film['casts']);
					}
					$OdeonFilm->write();
				}
				$r->push($OdeonFilm);
			}
		}
		return $r->sort("Title DESC");
	}

	public function updateAddress() {
		$RestfulService = new RestfulService("https://www.odeon.co.uk/cinemas/odeon/", 315360);

		$Response = $RestfulService->request($this->ID);
		if (!$Response->isError()) {
			$html = HtmlDomParser::str_get_html($Response->getBody());
			$cinema = $html->find('div[id="gethere"]', 0);
			foreach ($cinema->find('.span4') as $span4) {
				foreach ($span4->find('p.description') as $description) {
					$address = implode(', ', preg_split('/\s+\s+/', trim($description->plaintext)));

					$RestfulService = new RestfulService("http://maps.google.com/maps/api/geocode/json?address={$address}");
					$RestfulService_geo = $RestfulService->request();

					if (!$RestfulService_geo->isError()) {
						$body = Convert::json2array($RestfulService_geo->getBody());
						if (isset($body['results'][0]['geometry']['location']['lat']) && isset($body['results'][0]['geometry']['location']['lng'])) {
							$this->Address = $address;
							$this->lat = $body['results'][0]['geometry']['location']['lat'];
							$this->lng = $body['results'][0]['geometry']['location']['lng'];
							$this->write();
						}
					}
				}
			}
		}
	}

	public function getAddress() {
		if (!$this->getField('Address')) {

		}
		return $this->getField('Address');
	}

	public function requireDefaultRecords() {
		parent::requireDefaultRecords();

		if (!OdeonCinema::get()->count()) {

			$RestfulService = new RestfulService("http://www.odeon.co.uk/", 259200);

			$Response = $RestfulService->request();
			if (!$Response->isError()) {
				$html = HtmlDomParser::str_get_html($Response->getBody());
				$cinemas_select = $html->find('select[id="your-cinema"]', 0);
				foreach ($cinemas_select->find('option') as $option) {
					$OdeonCinema = OdeonCinema::get_by_id('OdeonCinema', (int)$option->value);
					if (!$OdeonCinema) {
						$OdeonCinema = new OdeonCinema();
						$OdeonCinema->ID = $option->value;
						$OdeonCinema->Title = $option->innertext;
						$OdeonCinema->write();
					}
				}
			}
		}
	}
}