En mi archivo php.ini tuve que agregar el certificado de SSL

de esta forma: curl.cainfo = "C:\Users\UTN\Documents\GitHub\prueba\cacert.pem"

se busca curl.cainfo y agreo la ruta del certificado, en este caso la del proyecto 