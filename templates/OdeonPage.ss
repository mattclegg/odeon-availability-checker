<% require themedCSS(styles) %><% require javascript(themes/bootstrap/js/jquery-1.7.2.min.js) %><% require javascript(themes/bootstrap/js/bootstrap.min.js) %><!DOCTYPE html>
<html lang="en">
<head>
	<% base_tag %>
	<title><% if MetaTitle %>$MetaTitle<% else %>$Title - $SiteConfig.Title<% end_if %></title>
	$MetaTags(false)
	<meta name="viewport" id="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=10.0,initial-scale=1.0" />
	<!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
	<!--[if lt IE 9]><script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script><![endif]-->

	<link rel="stylesheet" type="text/css" href="//api.tiles.mapbox.com/mapbox.js/v2.1.4/mapbox.css" />
</head>
<body>
	<% include IETop %>
	<header class="page-header">
		<div class="container">
			<div class="row">
				<div class="col-md-5">
					<h1><a href="home" accesskey="H" title="Odeon availability checker" class="brand">Odeon availability checker</a></h1><h2>* Not affiliated with Odeon</h2>
				</div>
				<div class="col-md-7"><div class="well">
					<p class="lead">Quickly check availability at Odeon</p><p>Tired of clicking through several screens, being asked to signup and then find that the only availability are for <i>Wheelchair Accessible</i> seats? Let us click through the screens for you.. Choose your cinema, then film and the only times shown do have availability!</p>
				</div></div>
			</div>
		</div>
	</header>
	<div id="top" class="container">
		<div id="content" class="typography">
			<div class="row">
				<div class="col-md-12">
					<% if CurrentCinema %>
						<% with CurrentCinema %>
							<h2>$Title</h2>
							<address>$Address</address>
						<% end_with %>
					<% end_if %>
				</div>
			</div>
			<div class="row">
				<div class="col-md-4">
					<div id='map'></div>
					<% if CurrentCinema %><% else %>
						<br/>
						<a href="/" title="$Title.XML" class="nohover">
							<div class="alert alert-success">
								<h5><span class="glyphicon glyphicon-arrow-left" aria-hidden="true"></span>&nbsp;View all cinemas</h5>
							</div>
						</a>
					<% end_if %>
				</div>
				<div class="col-md-8">
					<% if CurrentFilm %>
						<% if CurrentCinema %>
							<% with CurrentCinema %>
								<a href="$Link" title="$Title.XML" class="nohover">
									<div class="alert alert-success">
										<h5><span class="glyphicon glyphicon-arrow-left" aria-hidden="true"></span>&nbsp;View all films</h5>
									</div>
								</a>
							<% end_with %>
						<% end_if %>
						<% with CurrentFilm %>
							<div class="row">
								<div class="col-md-2">
									<% if imageUrlSmall %><img class="pull-right" src="$imageUrlSmall" title="$Title.XML"/><% end_if %>
								</div>
								<div class="col-md-10">
									<h3>$Title</h3>
									<p>$Content.LimitCharacters(256)</p>
								</div>
							</div>
						<% end_with %>
						<% if Top.CurrentScreenings %>
							<div class="row">
								<% loop Top.CurrentScreenings.GroupedBy(GroupedByTime) %>
									<div class="col-md-6">
										<h3>$GroupedByTime</h3>
										<% loop Children.GroupedBy(GroupedByTitle) %>
											<h4>$GroupedByTitle&nbsp;<% loop Children.First %><a href="$Link"><span class="badge">$Availability&nbsp;+</span>&nbsp;$Title</a><% end_loop %></h4>
										<% end_loop %>
									</div>
									<% if Even %></div><div class="row"><% end_if %>
								<% end_loop %>
							</div>
						<% else %>
							<h5>There are no showings available.</h5>
						<% end_if %>
					<% else %>
						<% if CurrentCinema %>
							<div class="row">
								<div class="col-md-6">
									<div class="alert alert-success">
										<h2><span class="glyphicon glyphicon-arrow-down" aria-hidden="true"></span>&nbsp;Choose film</h2>
									</div>
								</div>
							</div>
							<div class="row">
								<% with CurrentCinema %>
									<div class="col-md-6">
										<nav>
											<ul><% loop getCurrentFilms %>
												<li>
													<a href="{$Top.Link}check/$Top.CurrentCinema.ID/$ID">$Title.XML</a>
												</li>
											<% end_loop %></ul>
										</nav>
									</div>
								<% end_with %>
							</div>
						<% else %>
							<div class="row">
								<% loop AllCinemas %>
									<div class="col-md-3">
										<small><a href="$Link">$Title</a></small>
									</div>
								<% end_loop %>
							</div>
						<% end_if %>
					<% end_if %>
				</div>
			</div>
			$Form
			$PageComments
		</div>
	</div>
	<hr/>
	<footer>
		<div class="container"><% include Footer %></div>
	</footer>
	<% include IEBottom %>
	<script type="text/javascript" src="//api.tiles.mapbox.com/mapbox.js/v2.1.4/mapbox.js"></script>
	<script>
		L.mapbox.accessToken = 'pk.eyJ1IjoibWF0dGNsZWdnIiwiYSI6IlJ4aXBpb0UifQ.qWLj7LOD2wzKNo3gzaJvCg';
		var map = L.mapbox.map('map', 'mattclegg.k8k8jefo', {
			zoomControl: false
		})<% if CurrentCinema %><% with CurrentCinema %>.setView([$lat, $lng], 2).setZoom(12)<% end_with %><% else %>.setZoom(5)<% end_if %>;

		// Disable drag and zoom handlers.
		map.dragging.disable();
		map.touchZoom.disable();
		map.doubleClickZoom.disable();
		map.scrollWheelZoom.disable();

		// Disable tap handler, if present.
		if (map.tap) map.tap.disable();
		<% loop AllCinemas %>
			<% if Address %>
				L.mapbox.featureLayer({
					type: 'Feature',
					geometry: {
						type: 'Point',
						coordinates: [$lng, $lat]
					},
					properties: {
						title: '$Title.XML',
						description: '$Address.XML',
						'marker-symbol': 'cinema',
						<% if Top.CurrentCinema.ID = $ID %>
							'marker-size': 'large', 'marker-color': '#fdb914'
						<% else %>
							'marker-size': 'small', 'marker-color': '#333'
						<% end_if %>
					}
				}).addTo(map);
			<% end_if %>
		<% end_loop %>
	</script>
</body>
</html>
