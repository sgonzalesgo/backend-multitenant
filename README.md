
[//]: # ( Lo primero para poder trabajar con phpstorm)
`composer require --dev barryvdh/laravel-ide-helper`

[//]: # (Luego correr los siguientes comandos)
`php artisan ide-helper:generate`
`php artisan ide-helper:models -M`
`php artisan ide-helper:meta`

[//]: # (Pasos previos)
`php artisan passport:keys`
`php artisan passport:client --personal`

[//]: # (Corre seeders)
`php artisan db:seed --class=PermissionSeeder`
`php artisan db:seed --class=RoleSeeder`
`php artisan db:seed --class=UserSeeder`

[//]: # (Tenemos un servicio para identificar a las personas, esto lo usamos para cuando un usuario de un colegio quiere buscar a
[//]: # (un estudiante para matricularlo que es de otro colegio, para buscarlo se usa ese servicio&#41;)
[//]: # (Úsalo al buscar)
[//]: # (En tu controlador de búsqueda:)

$norm = PIS::normalize($type, $value);
$hex  = PIS::hash($norm);

$exists = PersonIdentifier::where('type', $type)
->where('value_hash', PIS::hexToBin($hex))
->exists();

[//]: # (Tenemos un provider llamado RouteServiceProvider que lo que hace es hacer mas corto el registro de las apis)
RouteServiceProvider.php

[//]: # (Las apis las probaremos en telescope)
`composer require laravel/telescope --dev`
`php artisan telescope:install`
`php artisan migrate`

.env
TELESCOPE_ENABLED=true
http://tu-app.test/telescope/commands

[//]: # (Lipiar)
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear
php artisan permission:cache-reset
=======
## link
php artisan storage:link

## Esto es para el chat en tiempo real
composer require pusher/pusher-php-server

## esto es para correr soketi en docker
docker run -p 6001:6001 \
-e DEBUG=1 \
-e PORT=6001 \
-e APP_ID=local-chat \
-e KEY=localkey \
-e SECRET=localsecret \
--name soketi \
quay.io/soketi/soketi:latest-16-alpine

[//]: # (comando para limpiar la cache de los router)
php artisan route:clear
php artisan route:list | grep refresh

// mailtrap
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=e89c0f90f6bd60
MAIL_PASSWORD=9c00903b8cd44a
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=admin@eduolivo.com
MAIL_FROM_NAME="Mi App"

