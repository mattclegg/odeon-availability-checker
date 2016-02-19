<?php

use Sunra\PhpSimple\HtmlDomParser;

class OdeonPage extends Page {

}

class OdeonPage_Controller extends Page_Controller {


	private static $allowed_actions = array(
		'check'
	);

	private $OdeonCinema = false;
	private $OdeonFilm = false;

	public function init() {
		parent::init();
		// You can include any CSS or JS required by your project here.
		// See: http://doc.silverstripe.org/framework/en/reference/requirements
	}

	public function check() {

		$OdeonCinemaID = (int)$this->request->param("ID");

		if ($OdeonCinemaID) {
			if ($this->OdeonCinema = OdeonCinema::get_by_id("OdeonCinema", $OdeonCinemaID)) {

				$this->OdeonCinema->getCurrentFilms();

				$OdeonFilmID = (int)$this->request->param("OtherID");

				if ($OdeonFilmID) {

					if ($this->OdeonFilm = OdeonFilm::get_by_id("OdeonFilm", $OdeonFilmID)) {

						$maxdays = 15;

						$baseURL = "https://www.odeon.co.uk/";

						$date = new Date();
						$RestfulService = new RestfulService($baseURL);

						$i = 0;
						do {
							$date->setValue("+{$i} day");

							if (!OdeonScreening::get("OdeonScreening", implode(" AND ", array(
								"DATE_FORMAT(ScreeningTime,'%d%m%y') = '{$date->Format("dmy")}'",
								"FilmID='{$OdeonFilmID}'",
								"CinemaID='{$OdeonCinemaID}'",
							)))->Count()) {

								$query = array(
									'date'			=> $date->Format("Y-m-d"),
									'siteId'		=> $OdeonCinemaID,
									'filmMasterId'	=> $OdeonFilmID,
									'type' => 'DAY'
								);

								$RestfulService->setQueryString($query);
								$Response = $RestfulService->request("showtimes/day");

								if (!$Response->isError()) {

									$html = HtmlDomParser::str_get_html($Response->getBody());
									foreach ($html->find('ul') as $ul) {
										foreach ($ul->find('li') as $li) {

											$ScreeningTime = new SS_Datetime();
											$ScreeningTime->setValue("{$date->Format("Y-m-d")} {$li->find('a', 0)->innertext}:00");

											$checkAgainstAPI = true;

											if ($OdeonScreening = OdeonScreening::get_one("OdeonScreening", implode(" AND ",
												array(
													"CinemaID='{$OdeonCinemaID}'",
													"FilmID='{$OdeonFilmID}'",
													"ScreeningTime='{$ScreeningTime->Rfc2822()}'"
												)))) {

												$checkAgainstAPI = $OdeonScreening->checkAgainstAPI();

											} else {
												$OdeonScreening = new OdeonScreening();
												$OdeonScreening->CinemaID = $OdeonCinemaID;
												$OdeonScreening->FilmID = $OdeonFilmID;
												$OdeonScreening->ScreeningTime = $ScreeningTime->Rfc2822();
											}

											if ($checkAgainstAPI) {

												$URLSegment = str_replace($baseURL, "", $li->find('a', 0)->href);

												$Response_init = $RestfulService->request($URLSegment, "GET", null, null, array(
													CURLOPT_COOKIESESSION=>TRUE
												));


												if (!$Response_init->isError()) {

													$dom = new DOMDocument();
													$dom->strictErrorChecking = FALSE;
													libxml_use_internal_errors(true);
													$dom->loadHTML($Response_init->getBody());
													libxml_clear_errors();

													$nodes = $dom->getElementsByTagName('form');
													$submit_url = false;

													$hidden_inputs = array();
													foreach ($nodes as $node) {
														if (!$submit_url && $node->hasAttributes()) {
															foreach ($node->attributes as $attribute) {
																if (!$submit_url && $attribute->nodeName == 'action') {
																	$submit_url = $attribute->nodeValue;
																}
															}
														}
													} unset($node);

													$SubmitURL = ltrim($submit_url, '/');


													$Cookies = $Response_init->getHeader("Set-Cookie");

													if (is_array($Cookies)) {
														$Cookies = implode(';', $Cookies);
													}

													$Response_availability = $RestfulService->request($SubmitURL, "GET", null, null, array(
														CURLOPT_COOKIE=>$Cookies
													));

													if (!$Response_availability->isError()) {

														$html_availability = HtmlDomParser::str_get_html($Response_availability->getBody());

														$ticketsTable = $html_availability->find('#tickets-table', 0);

														if ($ticketsTable) {

															$ticketsForm = $html_availability->find('#tickets', 0);

															$data = array(
																"submit"=>null
															);

															foreach ($ticketsTable->find('select') as $select) {
																$data[$select->attr["name"]] = "0";
															}

															foreach ($ticketsTable->find('tr') as $tr) {
																foreach ($tr->find('td') as $td) {
																	switch ($td->getAttribute("class")) {
																		case "ticket-col":
																			$OdeonScreening->Title = trim($td->innertext);
																			break;
																		case "price-col":
																			$OdeonScreening->Cost = ltrim(explode(" ", trim($td->plaintext))[0], '£');
																			break;
																		case "quantity-col":

																			$Availability = 1;

																			foreach ($td->find('select') as $select) {
																				foreach ($select->find('option') as $option) {
																					$Availability = $option->attr["value"];
																				}
																				$data[$select->attr["name"]] = $Availability;
																			}

																			$Response_seats = $RestfulService->request(ltrim(html_entity_decode($ticketsForm->attr['action']), "/"), "POST", $data, null, array(
																				CURLOPT_COOKIE=>$Cookies
																			));

																			if (!$Response_seats->isError()) {
																				$html_seats = HtmlDomParser::str_get_html($Response_seats->getBody());

																				if (trim($html_seats->find('.step-headline', 0)->innertext) == "Choose your seats") {
																					$OdeonScreening->Availability = $Availability;
																					$OdeonScreening->SessionURL = $URLSegment;
																					$OdeonScreening->duplicate();
																				}
																			}
																			break;
																	}
																}


															}
														}
													}
												}
											}
										}
									}
								} else {
									Debug::show($query);
									Debug::show($Response);
								}
							}
							$i++;
						} while ($i < $maxdays);

					} else {
						echo "Not a valid film ID";
					}
				}
			} else {
				echo "Not a valid cinema ID";
			}
		}
		return $this;
	}

	public function CurrentCinema() {
		return $this->OdeonCinema;
	}
	public function CurrentFilm() {
		return $this->OdeonFilm;
	}
	public function CurrentScreenings() {
		return GroupedList::create($this->CurrentFilm()->Screenings()->filter(array("CinemaID"=>$this->CurrentCinema()->ID)));
	}

	public function AllCinemas() {
		return OdeonCinema::get()->exclude(array("ID"=>"1"))->sort("Title");
	}

	private function AllFilms() {
		return OdeonFilm::get();
	}

}
