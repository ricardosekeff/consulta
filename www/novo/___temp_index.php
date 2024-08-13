<!DOCTYPE html>
<html lang="pt-BR">
<head>
	<meta charset="UTF-8">
	<meta name="theme-color" content="#009b2f">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
	<title>Consulta Fácil - A saúde que cabe no seu bolso</title>
	<link href="https://fonts.googleapis.com/css?family=Oswald" rel="stylesheet">
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
	<link rel="icon" href="favicon.ico" type="image/x-icon">
	<style>
		* { margin: 0; padding: 0; box-sizing: border-box; }
		div:after, ol:after, li:after, form:after, ul:after, dl:after {
			content: ".";
			display: block;
			clear: both;
			visibility: hidden;
			height: 0;
			overflow: hidden;
		}
		html, body { min-width: 100%; min-height: 100%; }
		body { padding: 30px 20px; font-family: 'Oswald', sans-serif; text-align: center; background: url('medico.png') right bottom no-repeat; background-size: contain; color: #363636; }
		img { max-width: 100%; }
		.logo { margin: 80px 0 10px; }
		.slogan { margin-bottom: 30px; font-size: 34px; }
		.aguarde { text-transform: uppercase; font-size: 46px; }

		@media (max-width: 700px) {
			html::before { position: absolute; display: block; width: 100%; height: 100%; content: ''; top: 0; left: 0; background: rgba(255,255,255,0.7); z-index: -1; }
			.logo { margin-top: 40px; }
			.slogan { font-size: 28px; }
			.aguarde { font-size: 38px; }
		}
	</style>
</head>
<body>
	<img src="logo.png" class="logo" alt="Consulta Fácil" />
	<p class="slogan">A saúde que cabe no seu bolso</p>
	<p class="aguarde">Aguarde!</p>
</body>
</html>