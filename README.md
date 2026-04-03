
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

// Esto es para que el redis funcione, es para correr el worker y que pueda enviar mensajes por canales broadcast
php artisan queue:work

// es el comando que levanta el servidor WebSocket de Laravel Reverb
//Sirve para que tu app pueda:
//aceptar conexiones websocket desde el frontend (Echo / WebSocket)
//crear canales (private-group..., presence...)
//emitir eventos en tiempo real (broadcast)
//Sin Reverb corriendo, no hay “tiempo real” aunque el evento se dispare.
php artisan reverb:start
 --- o ----
php artisan reverb:start --host=0.0.0.0 --port=8080 --debug

3) OJO CRÍTICO: tus cookies están en secure=true (en local HTTP no se guardan)
Aunque CORS esté perfecto, si tu backend setea cookies con secure=true y estás en http://localhost, el navegador NO las guarda.
Para local, haz:
secure = false
SameSite = Lax (local)
Y para prod HTTPS:
secure = true
SameSite = None (si frontend y backend están en dominios distintos)

Para ver el chat y demas cosas relacionadas con mensajes de chat
1-Recuerda hechar a andar el redis en el docker, 
2-luego hacer el login
3- ejecutar este comando: docker exec -it redis redis-cli -n 1 --scan --pattern "*presence:online:*"
el resultado es este, fijate como tenemos el id del tenant y el id del usuario que esta logueado:
    Parte	         Valor
    Prefix Laravel	 school_chat_presence:online
    tenantId	     baf21964-0286-46cc-b611-42bf49c7bd41
    userId	         77156bc3-f53a-4f0e-b6ff-190fb71b78a4
Para ver los online de un tenant:
docker exec -it redis redis-cli -n 1 --scan \
--pattern "school_chat_presence:online:baf21964-0286-46cc-b611-42bf49c7bd41:*"

Para extraer el user id de los que estan logueados:
docker exec -it redis sh -lc \
'redis-cli -n 1 --scan --pattern "*presence:online:*" | awk -F: "{print \$NF}"'

Para ver si sigue online:
docker exec -it redis redis-cli -n 1 TTL \
"school_chat_presence:online:baf21964-0286-46cc-b611-42bf49c7bd41:77156bc3-f53a-4f0e-b6ff-190fb71b78a4"
    Estos son los posibles resultados:
        | Valor | Significado   |
        | ----- | ------------- |
        | >0    | sigue online  |
        | -2    | offline       |
        | -1    | bug (sin TTL) |

Para invitar a un usuario a un grupo creado debemos hacer estos pasos:
1-Crear el grupo
2-Enviar la invitacion (POST /groups/{groupId}/invite)
3-Llamamos a invitations (GET /groups/invitations)
4-Llamamos a accept (POST /groups/{groupId}/accept) 
5- Si queremos rechazar la invitacion llamamos a reject (POST /groups/{groupId}/reject)

Para ver que usuarios estan registrados en un grupo debemos llamar a:
GET /groups/{groupId}/members

6) Pruebas de “comportamiento” (para saber que está bien)
   A) Antes de aceptar
    Como invitado, prueba:
    GET /groups/{groupId}/messages
    ✅ Debe dar:
    403 (no es accepted)
    B) Después de aceptar
    Como invitado, prueba:
    GET /groups/{groupId}/messages → ✅ 200
    POST /groups/{groupId}/messages con body { "body": "Hola" } → ✅ 201


## setear la zona horaria en postgres (esto es importante para que funcione el datetime)
SET TIME ZONE 'UTC';
SHOW timezone;


[//]: # (listado de permisos que debe tener por defecto un usuario
List audit_logs
List notifications
Read notifications

