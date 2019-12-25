<html>
	<head>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
		<script>

			function again(url){
				$.get( url, function( data ) {
					console.log(data);
					again(data);

				});
			}

			again('http://exodusnc-wordpress.corcrm.com/?importshopify=in091&pagenum=1');

		</script>
	</head>
<body>
</body>
</html>