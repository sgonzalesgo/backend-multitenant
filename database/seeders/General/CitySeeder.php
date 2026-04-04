<?php

namespace Database\Seeders\General;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $states = DB::table('states')
            ->join('countries', 'countries.id', '=', 'states.country_id')
            ->select(
                'states.id',
                'states.code as state_code',
                'countries.code as country_code'
            )
            ->get()
            ->mapWithKeys(function ($item) {
                return [
                    "{$item->country_code}_{$item->state_code}" => $item->id
                ];
            });

        $cities = [

            // 🇪🇨 ECUADOR

            // Azuay
            ['state_id' => $states['EC_AZUAY'], 'name' => 'Cuenca'],
            ['state_id' => $states['EC_AZUAY'], 'name' => 'Girón'],
            ['state_id' => $states['EC_AZUAY'], 'name' => 'Gualaceo'],
            ['state_id' => $states['EC_AZUAY'], 'name' => 'Nabón'],
            ['state_id' => $states['EC_AZUAY'], 'name' => 'Paute'],
            ['state_id' => $states['EC_AZUAY'], 'name' => 'Pucará'],
            ['state_id' => $states['EC_AZUAY'], 'name' => 'San Fernando'],
            ['state_id' => $states['EC_AZUAY'], 'name' => 'Santa Isabel'],
            ['state_id' => $states['EC_AZUAY'], 'name' => 'Sígsig'],
            ['state_id' => $states['EC_AZUAY'], 'name' => 'Oña'],
            ['state_id' => $states['EC_AZUAY'], 'name' => 'Chordeleg'],
            ['state_id' => $states['EC_AZUAY'], 'name' => 'El Pan'],
            ['state_id' => $states['EC_AZUAY'], 'name' => 'Sevilla de Oro'],
            ['state_id' => $states['EC_AZUAY'], 'name' => 'Guachapala'],
            ['state_id' => $states['EC_AZUAY'], 'name' => 'Camilo Ponce Enríquez'],

            // Bolívar
            ['state_id' => $states['EC_BOLIVAR'], 'name' => 'Guaranda'],
            ['state_id' => $states['EC_BOLIVAR'], 'name' => 'Chillanes'],
            ['state_id' => $states['EC_BOLIVAR'], 'name' => 'Chimbo'],
            ['state_id' => $states['EC_BOLIVAR'], 'name' => 'Echeandía'],
            ['state_id' => $states['EC_BOLIVAR'], 'name' => 'San Miguel'],
            ['state_id' => $states['EC_BOLIVAR'], 'name' => 'Caluma'],
            ['state_id' => $states['EC_BOLIVAR'], 'name' => 'Las Naves'],

            // Cañar
            ['state_id' => $states['EC_CANAR'], 'name' => 'Azogues'],
            ['state_id' => $states['EC_CANAR'], 'name' => 'Biblián'],
            ['state_id' => $states['EC_CANAR'], 'name' => 'Cañar'],
            ['state_id' => $states['EC_CANAR'], 'name' => 'La Troncal'],
            ['state_id' => $states['EC_CANAR'], 'name' => 'El Tambo'],
            ['state_id' => $states['EC_CANAR'], 'name' => 'Déleg'],
            ['state_id' => $states['EC_CANAR'], 'name' => 'Suscal'],

            // Carchi
            ['state_id' => $states['EC_CARCHI'], 'name' => 'Tulcán'],
            ['state_id' => $states['EC_CARCHI'], 'name' => 'Bolívar'],
            ['state_id' => $states['EC_CARCHI'], 'name' => 'Espejo'],
            ['state_id' => $states['EC_CARCHI'], 'name' => 'Mira'],
            ['state_id' => $states['EC_CARCHI'], 'name' => 'Montúfar'],
            ['state_id' => $states['EC_CARCHI'], 'name' => 'San Pedro de Huaca'],

            // Chimborazo
            ['state_id' => $states['EC_CHIMBORAZO'], 'name' => 'Riobamba'],
            ['state_id' => $states['EC_CHIMBORAZO'], 'name' => 'Alausí'],
            ['state_id' => $states['EC_CHIMBORAZO'], 'name' => 'Colta'],
            ['state_id' => $states['EC_CHIMBORAZO'], 'name' => 'Chambo'],
            ['state_id' => $states['EC_CHIMBORAZO'], 'name' => 'Chunchi'],
            ['state_id' => $states['EC_CHIMBORAZO'], 'name' => 'Guamote'],
            ['state_id' => $states['EC_CHIMBORAZO'], 'name' => 'Guano'],
            ['state_id' => $states['EC_CHIMBORAZO'], 'name' => 'Pallatanga'],
            ['state_id' => $states['EC_CHIMBORAZO'], 'name' => 'Penipe'],
            ['state_id' => $states['EC_CHIMBORAZO'], 'name' => 'Cumandá'],

            // Cotopaxi
            ['state_id' => $states['EC_COTOPAXI'], 'name' => 'Latacunga'],
            ['state_id' => $states['EC_COTOPAXI'], 'name' => 'La Maná'],
            ['state_id' => $states['EC_COTOPAXI'], 'name' => 'Pangua'],
            ['state_id' => $states['EC_COTOPAXI'], 'name' => 'Pujilí'],
            ['state_id' => $states['EC_COTOPAXI'], 'name' => 'Salcedo'],
            ['state_id' => $states['EC_COTOPAXI'], 'name' => 'Saquisilí'],
            ['state_id' => $states['EC_COTOPAXI'], 'name' => 'Sigchos'],

            // El Oro
            ['state_id' => $states['EC_EL_ORO'], 'name' => 'Machala'],
            ['state_id' => $states['EC_EL_ORO'], 'name' => 'Arenillas'],
            ['state_id' => $states['EC_EL_ORO'], 'name' => 'Atahualpa'],
            ['state_id' => $states['EC_EL_ORO'], 'name' => 'Balsas'],
            ['state_id' => $states['EC_EL_ORO'], 'name' => 'Chilla'],
            ['state_id' => $states['EC_EL_ORO'], 'name' => 'El Guabo'],
            ['state_id' => $states['EC_EL_ORO'], 'name' => 'Huaquillas'],
            ['state_id' => $states['EC_EL_ORO'], 'name' => 'Las Lajas'],
            ['state_id' => $states['EC_EL_ORO'], 'name' => 'Marcabelí'],
            ['state_id' => $states['EC_EL_ORO'], 'name' => 'Pasaje'],
            ['state_id' => $states['EC_EL_ORO'], 'name' => 'Piñas'],
            ['state_id' => $states['EC_EL_ORO'], 'name' => 'Portovelo'],
            ['state_id' => $states['EC_EL_ORO'], 'name' => 'Santa Rosa'],
            ['state_id' => $states['EC_EL_ORO'], 'name' => 'Zaruma'],

            // Esmeraldas
            ['state_id' => $states['EC_ESMERALDAS'], 'name' => 'Esmeraldas'],
            ['state_id' => $states['EC_ESMERALDAS'], 'name' => 'Atacames'],
            ['state_id' => $states['EC_ESMERALDAS'], 'name' => 'Eloy Alfaro'],
            ['state_id' => $states['EC_ESMERALDAS'], 'name' => 'Muisne'],
            ['state_id' => $states['EC_ESMERALDAS'], 'name' => 'Quinindé'],
            ['state_id' => $states['EC_ESMERALDAS'], 'name' => 'Rioverde'],
            ['state_id' => $states['EC_ESMERALDAS'], 'name' => 'San Lorenzo'],
            ['state_id' => $states['EC_ESMERALDAS'], 'name' => 'Tonsupa'],
            ['state_id' => $states['EC_ESMERALDAS'], 'name' => 'Same'],
            ['state_id' => $states['EC_ESMERALDAS'], 'name' => 'Súa'],
            ['state_id' => $states['EC_ESMERALDAS'], 'name' => 'Borbón'],
            ['state_id' => $states['EC_ESMERALDAS'], 'name' => 'Limones'],
            ['state_id' => $states['EC_ESMERALDAS'], 'name' => 'Valdez'],
            ['state_id' => $states['EC_ESMERALDAS'], 'name' => 'La Tola'],
            ['state_id' => $states['EC_ESMERALDAS'], 'name' => 'Tonchigüe'],
            ['state_id' => $states['EC_ESMERALDAS'], 'name' => 'Viche'],
            ['state_id' => $states['EC_ESMERALDAS'], 'name' => 'Cube'],

            // Galápagos
            ['state_id' => $states['EC_GALAPAGOS'], 'name' => 'Puerto Baquerizo Moreno'],
            ['state_id' => $states['EC_GALAPAGOS'], 'name' => 'Puerto Ayora'],
            ['state_id' => $states['EC_GALAPAGOS'], 'name' => 'Puerto Villamil'],

            // Guayas
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'Guayaquil'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'Alfredo Baquerizo Moreno'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'Balao'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'Balzar'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'Colimes'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'Daule'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'Durán'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'El Empalme'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'El Triunfo'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'General Antonio Elizalde'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'Isidro Ayora'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'Lomas de Sargentillo'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'Milagro'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'Naranjal'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'Naranjito'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'Nobol'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'Palestina'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'Pedro Carbo'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'Playas'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'Salitre'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'Samborondón'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'Santa Lucía'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'Simón Bolívar'],
            ['state_id' => $states['EC_GUAYAS'], 'name' => 'Yaguachi'],

            // Imbabura
            ['state_id' => $states['EC_IMBABURA'], 'name' => 'Ibarra'],
            ['state_id' => $states['EC_IMBABURA'], 'name' => 'Antonio Ante'],
            ['state_id' => $states['EC_IMBABURA'], 'name' => 'Cotacachi'],
            ['state_id' => $states['EC_IMBABURA'], 'name' => 'Otavalo'],
            ['state_id' => $states['EC_IMBABURA'], 'name' => 'Pimampiro'],
            ['state_id' => $states['EC_IMBABURA'], 'name' => 'San Miguel de Urcuquí'],
            ['state_id' => $states['EC_IMBABURA'], 'name' => 'Atuntaqui'],

            // Loja
            ['state_id' => $states['EC_LOJA'], 'name' => 'Loja'],
            ['state_id' => $states['EC_LOJA'], 'name' => 'Calvas'],
            ['state_id' => $states['EC_LOJA'], 'name' => 'Catamayo'],
            ['state_id' => $states['EC_LOJA'], 'name' => 'Celica'],
            ['state_id' => $states['EC_LOJA'], 'name' => 'Chaguarpamba'],
            ['state_id' => $states['EC_LOJA'], 'name' => 'Espíndola'],
            ['state_id' => $states['EC_LOJA'], 'name' => 'Gonzanamá'],
            ['state_id' => $states['EC_LOJA'], 'name' => 'Macará'],
            ['state_id' => $states['EC_LOJA'], 'name' => 'Olmedo'],
            ['state_id' => $states['EC_LOJA'], 'name' => 'Paltas'],
            ['state_id' => $states['EC_LOJA'], 'name' => 'Pindal'],
            ['state_id' => $states['EC_LOJA'], 'name' => 'Puyango'],
            ['state_id' => $states['EC_LOJA'], 'name' => 'Quilanga'],
            ['state_id' => $states['EC_LOJA'], 'name' => 'Saraguro'],
            ['state_id' => $states['EC_LOJA'], 'name' => 'Sozoranga'],
            ['state_id' => $states['EC_LOJA'], 'name' => 'Zapotillo'],
            ['state_id' => $states['EC_LOJA'], 'name' => 'Catacocha'],
            ['state_id' => $states['EC_LOJA'], 'name' => 'Cariamanga'],
            ['state_id' => $states['EC_LOJA'], 'name' => 'Alamor'],

            // Los Ríos
            ['state_id' => $states['EC_LOS_RIOS'], 'name' => 'Babahoyo'],
            ['state_id' => $states['EC_LOS_RIOS'], 'name' => 'Baba'],
            ['state_id' => $states['EC_LOS_RIOS'], 'name' => 'Buena Fe'],
            ['state_id' => $states['EC_LOS_RIOS'], 'name' => 'Mocache'],
            ['state_id' => $states['EC_LOS_RIOS'], 'name' => 'Montalvo'],
            ['state_id' => $states['EC_LOS_RIOS'], 'name' => 'Palenque'],
            ['state_id' => $states['EC_LOS_RIOS'], 'name' => 'Puebloviejo'],
            ['state_id' => $states['EC_LOS_RIOS'], 'name' => 'Quevedo'],
            ['state_id' => $states['EC_LOS_RIOS'], 'name' => 'Quinsaloma'],
            ['state_id' => $states['EC_LOS_RIOS'], 'name' => 'Urdaneta'],
            ['state_id' => $states['EC_LOS_RIOS'], 'name' => 'Valencia'],
            ['state_id' => $states['EC_LOS_RIOS'], 'name' => 'Ventanas'],
            ['state_id' => $states['EC_LOS_RIOS'], 'name' => 'Vinces'],

            // Manabí
            ['state_id' => $states['EC_MANABI'], 'name' => 'Portoviejo'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'Bolívar'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'Chone'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'El Carmen'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'Flavio Alfaro'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'Jama'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'Jaramijó'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'Jipijapa'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'Junín'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'Manta'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'Montecristi'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'Olmedo'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'Paján'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'Pedernales'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'Pichincha'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'Puerto López'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'Rocafuerte'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'Santa Ana'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'Sucre'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'Tosagua'],
            ['state_id' => $states['EC_MANABI'], 'name' => '24 de Mayo'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'San Vicente'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'Bahía de Caráquez'],
            ['state_id' => $states['EC_MANABI'], 'name' => 'Calceta'],

            // Morona Santiago
            ['state_id' => $states['EC_MORONA_SANTIAGO'], 'name' => 'Macas'],
            ['state_id' => $states['EC_MORONA_SANTIAGO'], 'name' => 'Gualaquiza'],
            ['state_id' => $states['EC_MORONA_SANTIAGO'], 'name' => 'Huamboya'],
            ['state_id' => $states['EC_MORONA_SANTIAGO'], 'name' => 'Limón Indanza'],
            ['state_id' => $states['EC_MORONA_SANTIAGO'], 'name' => 'Logroño'],
            ['state_id' => $states['EC_MORONA_SANTIAGO'], 'name' => 'Morona'],
            ['state_id' => $states['EC_MORONA_SANTIAGO'], 'name' => 'Pablo Sexto'],
            ['state_id' => $states['EC_MORONA_SANTIAGO'], 'name' => 'Palora'],
            ['state_id' => $states['EC_MORONA_SANTIAGO'], 'name' => 'San Juan Bosco'],
            ['state_id' => $states['EC_MORONA_SANTIAGO'], 'name' => 'Santiago'],
            ['state_id' => $states['EC_MORONA_SANTIAGO'], 'name' => 'Sucúa'],
            ['state_id' => $states['EC_MORONA_SANTIAGO'], 'name' => 'Taisha'],
            ['state_id' => $states['EC_MORONA_SANTIAGO'], 'name' => 'Tiwintza'],

            // Napo
            ['state_id' => $states['EC_NAPO'], 'name' => 'Tena'],
            ['state_id' => $states['EC_NAPO'], 'name' => 'Archidona'],
            ['state_id' => $states['EC_NAPO'], 'name' => 'Carlos Julio Arosemena Tola'],
            ['state_id' => $states['EC_NAPO'], 'name' => 'El Chaco'],
            ['state_id' => $states['EC_NAPO'], 'name' => 'Quijos'],

            // Orellana
            ['state_id' => $states['EC_ORELLANA'], 'name' => 'Puerto Francisco de Orellana'],
            ['state_id' => $states['EC_ORELLANA'], 'name' => 'Aguarico'],
            ['state_id' => $states['EC_ORELLANA'], 'name' => 'La Joya de los Sachas'],
            ['state_id' => $states['EC_ORELLANA'], 'name' => 'Loreto'],

            // Pastaza
            ['state_id' => $states['EC_PASTAZA'], 'name' => 'Puyo'],
            ['state_id' => $states['EC_PASTAZA'], 'name' => 'Arajuno'],
            ['state_id' => $states['EC_PASTAZA'], 'name' => 'Mera'],
            ['state_id' => $states['EC_PASTAZA'], 'name' => 'Santa Clara'],

            // Pichincha
            ['state_id' => $states['EC_PICHINCHA'], 'name' => 'Quito'],
            ['state_id' => $states['EC_PICHINCHA'], 'name' => 'Cayambe'],
            ['state_id' => $states['EC_PICHINCHA'], 'name' => 'Mejía'],
            ['state_id' => $states['EC_PICHINCHA'], 'name' => 'Pedro Moncayo'],
            ['state_id' => $states['EC_PICHINCHA'], 'name' => 'Pedro Vicente Maldonado'],
            ['state_id' => $states['EC_PICHINCHA'], 'name' => 'Puerto Quito'],
            ['state_id' => $states['EC_PICHINCHA'], 'name' => 'Rumiñahui'],
            ['state_id' => $states['EC_PICHINCHA'], 'name' => 'San Miguel de los Bancos'],
            ['state_id' => $states['EC_PICHINCHA'], 'name' => 'Sangolquí'],
            ['state_id' => $states['EC_PICHINCHA'], 'name' => 'Machachi'],
            ['state_id' => $states['EC_PICHINCHA'], 'name' => 'Tabacundo'],

            // Santa Elena
            ['state_id' => $states['EC_SANTA_ELENA'], 'name' => 'Santa Elena'],
            ['state_id' => $states['EC_SANTA_ELENA'], 'name' => 'La Libertad'],
            ['state_id' => $states['EC_SANTA_ELENA'], 'name' => 'Salinas'],

            // Santo Domingo de los Tsáchilas
            ['state_id' => $states['EC_SANTO_DOMINGO'], 'name' => 'Santo Domingo'],
            ['state_id' => $states['EC_SANTO_DOMINGO'], 'name' => 'La Concordia'],

            // Sucumbíos
            ['state_id' => $states['EC_SUCUMBIOS'], 'name' => 'Nueva Loja'],
            ['state_id' => $states['EC_SUCUMBIOS'], 'name' => 'Cascales'],
            ['state_id' => $states['EC_SUCUMBIOS'], 'name' => 'Cuyabeno'],
            ['state_id' => $states['EC_SUCUMBIOS'], 'name' => 'Gonzalo Pizarro'],
            ['state_id' => $states['EC_SUCUMBIOS'], 'name' => 'Putumayo'],
            ['state_id' => $states['EC_SUCUMBIOS'], 'name' => 'Shushufindi'],
            ['state_id' => $states['EC_SUCUMBIOS'], 'name' => 'Sucumbíos'],

            // Tungurahua
            ['state_id' => $states['EC_TUNGURAHUA'], 'name' => 'Ambato'],
            ['state_id' => $states['EC_TUNGURAHUA'], 'name' => 'Baños de Agua Santa'],
            ['state_id' => $states['EC_TUNGURAHUA'], 'name' => 'Cevallos'],
            ['state_id' => $states['EC_TUNGURAHUA'], 'name' => 'Mocha'],
            ['state_id' => $states['EC_TUNGURAHUA'], 'name' => 'Patate'],
            ['state_id' => $states['EC_TUNGURAHUA'], 'name' => 'Pelileo'],
            ['state_id' => $states['EC_TUNGURAHUA'], 'name' => 'Píllaro'],
            ['state_id' => $states['EC_TUNGURAHUA'], 'name' => 'Quero'],
            ['state_id' => $states['EC_TUNGURAHUA'], 'name' => 'Tisaleo'],

            // Zamora Chinchipe
            ['state_id' => $states['EC_ZAMORA_CHINCHIPE'], 'name' => 'Zamora'],
            ['state_id' => $states['EC_ZAMORA_CHINCHIPE'], 'name' => 'Centinela del Cóndor'],
            ['state_id' => $states['EC_ZAMORA_CHINCHIPE'], 'name' => 'Chinchipe'],
            ['state_id' => $states['EC_ZAMORA_CHINCHIPE'], 'name' => 'El Pangui'],
            ['state_id' => $states['EC_ZAMORA_CHINCHIPE'], 'name' => 'Nangaritza'],
            ['state_id' => $states['EC_ZAMORA_CHINCHIPE'], 'name' => 'Palanda'],
            ['state_id' => $states['EC_ZAMORA_CHINCHIPE'], 'name' => 'Paquisha'],
            ['state_id' => $states['EC_ZAMORA_CHINCHIPE'], 'name' => 'Yacuambi'],
            ['state_id' => $states['EC_ZAMORA_CHINCHIPE'], 'name' => 'Yantzaza'],
            ['state_id' => $states['EC_ZAMORA_CHINCHIPE'], 'name' => 'Zumba'],

            // 🇨🇴 COLOMBIA

// Amazonas
            ['state_id' => $states['CO_AMAZONAS'], 'name' => 'Leticia'],
            ['state_id' => $states['CO_AMAZONAS'], 'name' => 'Puerto Nariño'],

// Antioquia
            ['state_id' => $states['CO_ANTIOQUIA'], 'name' => 'Medellín'],
            ['state_id' => $states['CO_ANTIOQUIA'], 'name' => 'Bello'],
            ['state_id' => $states['CO_ANTIOQUIA'], 'name' => 'Itagüí'],
            ['state_id' => $states['CO_ANTIOQUIA'], 'name' => 'Envigado'],
            ['state_id' => $states['CO_ANTIOQUIA'], 'name' => 'Rionegro'],
            ['state_id' => $states['CO_ANTIOQUIA'], 'name' => 'Turbo'],
            ['state_id' => $states['CO_ANTIOQUIA'], 'name' => 'Apartadó'],
            ['state_id' => $states['CO_ANTIOQUIA'], 'name' => 'Carepa'],
            ['state_id' => $states['CO_ANTIOQUIA'], 'name' => 'Caucasia'],
            ['state_id' => $states['CO_ANTIOQUIA'], 'name' => 'Copacabana'],
            ['state_id' => $states['CO_ANTIOQUIA'], 'name' => 'La Ceja'],
            ['state_id' => $states['CO_ANTIOQUIA'], 'name' => 'Marinilla'],
            ['state_id' => $states['CO_ANTIOQUIA'], 'name' => 'Sabaneta'],
            ['state_id' => $states['CO_ANTIOQUIA'], 'name' => 'Girardota'],
            ['state_id' => $states['CO_ANTIOQUIA'], 'name' => 'Santa Fe de Antioquia'],

// Arauca
            ['state_id' => $states['CO_ARAUCA'], 'name' => 'Arauca'],
            ['state_id' => $states['CO_ARAUCA'], 'name' => 'Saravena'],
            ['state_id' => $states['CO_ARAUCA'], 'name' => 'Arauquita'],
            ['state_id' => $states['CO_ARAUCA'], 'name' => 'Tame'],
            ['state_id' => $states['CO_ARAUCA'], 'name' => 'Fortul'],

// Atlántico
            ['state_id' => $states['CO_ATLANTICO'], 'name' => 'Barranquilla'],
            ['state_id' => $states['CO_ATLANTICO'], 'name' => 'Soledad'],
            ['state_id' => $states['CO_ATLANTICO'], 'name' => 'Malambo'],
            ['state_id' => $states['CO_ATLANTICO'], 'name' => 'Puerto Colombia'],
            ['state_id' => $states['CO_ATLANTICO'], 'name' => 'Sabanalarga'],
            ['state_id' => $states['CO_ATLANTICO'], 'name' => 'Baranoa'],
            ['state_id' => $states['CO_ATLANTICO'], 'name' => 'Galapa'],

// Bolívar
            ['state_id' => $states['CO_BOLIVAR'], 'name' => 'Cartagena'],
            ['state_id' => $states['CO_BOLIVAR'], 'name' => 'Magangué'],
            ['state_id' => $states['CO_BOLIVAR'], 'name' => 'Turbaco'],
            ['state_id' => $states['CO_BOLIVAR'], 'name' => 'Arjona'],
            ['state_id' => $states['CO_BOLIVAR'], 'name' => 'El Carmen de Bolívar'],
            ['state_id' => $states['CO_BOLIVAR'], 'name' => 'Mompox'],
            ['state_id' => $states['CO_BOLIVAR'], 'name' => 'Simití'],

// Boyacá
            ['state_id' => $states['CO_BOYACA'], 'name' => 'Tunja'],
            ['state_id' => $states['CO_BOYACA'], 'name' => 'Duitama'],
            ['state_id' => $states['CO_BOYACA'], 'name' => 'Sogamoso'],
            ['state_id' => $states['CO_BOYACA'], 'name' => 'Chiquinquirá'],
            ['state_id' => $states['CO_BOYACA'], 'name' => 'Paipa'],
            ['state_id' => $states['CO_BOYACA'], 'name' => 'Puerto Boyacá'],
            ['state_id' => $states['CO_BOYACA'], 'name' => 'Moniquirá'],
            ['state_id' => $states['CO_BOYACA'], 'name' => 'Villa de Leyva'],

// Caldas
            ['state_id' => $states['CO_CALDAS'], 'name' => 'Manizales'],
            ['state_id' => $states['CO_CALDAS'], 'name' => 'La Dorada'],
            ['state_id' => $states['CO_CALDAS'], 'name' => 'Chinchiná'],
            ['state_id' => $states['CO_CALDAS'], 'name' => 'Riosucio'],
            ['state_id' => $states['CO_CALDAS'], 'name' => 'Villamaría'],
            ['state_id' => $states['CO_CALDAS'], 'name' => 'Anserma'],

// Caquetá
            ['state_id' => $states['CO_CAQUETA'], 'name' => 'Florencia'],
            ['state_id' => $states['CO_CAQUETA'], 'name' => 'San Vicente del Caguán'],
            ['state_id' => $states['CO_CAQUETA'], 'name' => 'Puerto Rico'],
            ['state_id' => $states['CO_CAQUETA'], 'name' => 'El Doncello'],
            ['state_id' => $states['CO_CAQUETA'], 'name' => 'Belén de los Andaquíes'],

            // Casanare
            ['state_id' => $states['CO_CASANARE'], 'name' => 'Yopal'],
            ['state_id' => $states['CO_CASANARE'], 'name' => 'Aguazul'],
            ['state_id' => $states['CO_CASANARE'], 'name' => 'Villanueva'],
            ['state_id' => $states['CO_CASANARE'], 'name' => 'Monterrey'],
            ['state_id' => $states['CO_CASANARE'], 'name' => 'Paz de Ariporo'],
            ['state_id' => $states['CO_CASANARE'], 'name' => 'Tauramena'],

            // Cauca
            ['state_id' => $states['CO_CAUCA'], 'name' => 'Popayán'],
            ['state_id' => $states['CO_CAUCA'], 'name' => 'Santander de Quilichao'],
            ['state_id' => $states['CO_CAUCA'], 'name' => 'Puerto Tejada'],
            ['state_id' => $states['CO_CAUCA'], 'name' => 'Patía'],
            ['state_id' => $states['CO_CAUCA'], 'name' => 'Piendamó'],
            ['state_id' => $states['CO_CAUCA'], 'name' => 'Miranda'],
            ['state_id' => $states['CO_CAUCA'], 'name' => 'Guapi'],

            // Cesar
            ['state_id' => $states['CO_CESAR'], 'name' => 'Valledupar'],
            ['state_id' => $states['CO_CESAR'], 'name' => 'Aguachica'],
            ['state_id' => $states['CO_CESAR'], 'name' => 'Bosconia'],
            ['state_id' => $states['CO_CESAR'], 'name' => 'Agustín Codazzi'],
            ['state_id' => $states['CO_CESAR'], 'name' => 'La Jagua de Ibirico'],
            ['state_id' => $states['CO_CESAR'], 'name' => 'Curumaní'],

            // Chocó
            ['state_id' => $states['CO_CHOCO'], 'name' => 'Quibdó'],
            ['state_id' => $states['CO_CHOCO'], 'name' => 'Istmina'],
            ['state_id' => $states['CO_CHOCO'], 'name' => 'Condoto'],
            ['state_id' => $states['CO_CHOCO'], 'name' => 'Tadó'],
            ['state_id' => $states['CO_CHOCO'], 'name' => 'Bahía Solano'],

            // Córdoba
            ['state_id' => $states['CO_CORDOBA'], 'name' => 'Montería'],
            ['state_id' => $states['CO_CORDOBA'], 'name' => 'Cereté'],
            ['state_id' => $states['CO_CORDOBA'], 'name' => 'Lorica'],
            ['state_id' => $states['CO_CORDOBA'], 'name' => 'Sahagún'],
            ['state_id' => $states['CO_CORDOBA'], 'name' => 'Planeta Rica'],
            ['state_id' => $states['CO_CORDOBA'], 'name' => 'Montelíbano'],
            ['state_id' => $states['CO_CORDOBA'], 'name' => 'Tierralta'],
            ['state_id' => $states['CO_CORDOBA'], 'name' => 'Chinú'],

            // Cundinamarca
            ['state_id' => $states['CO_CUNDINAMARCA'], 'name' => 'Soacha'],
            ['state_id' => $states['CO_CUNDINAMARCA'], 'name' => 'Facatativá'],
            ['state_id' => $states['CO_CUNDINAMARCA'], 'name' => 'Zipaquirá'],
            ['state_id' => $states['CO_CUNDINAMARCA'], 'name' => 'Chía'],
            ['state_id' => $states['CO_CUNDINAMARCA'], 'name' => 'Girardot'],
            ['state_id' => $states['CO_CUNDINAMARCA'], 'name' => 'Fusagasugá'],
            ['state_id' => $states['CO_CUNDINAMARCA'], 'name' => 'Mosquera'],
            ['state_id' => $states['CO_CUNDINAMARCA'], 'name' => 'Madrid'],
            ['state_id' => $states['CO_CUNDINAMARCA'], 'name' => 'Funza'],
            ['state_id' => $states['CO_CUNDINAMARCA'], 'name' => 'Cajicá'],
            ['state_id' => $states['CO_CUNDINAMARCA'], 'name' => 'La Calera'],
            ['state_id' => $states['CO_CUNDINAMARCA'], 'name' => 'Villeta'],

            // Bogotá
            ['state_id' => $states['CO_BOGOTA'], 'name' => 'Bogotá'],

            // 🇵🇪 PERÚ

// Amazonas
            ['state_id' => $states['PE_AMAZONAS'], 'name' => 'Chachapoyas'],
            ['state_id' => $states['PE_AMAZONAS'], 'name' => 'Bagua'],
            ['state_id' => $states['PE_AMAZONAS'], 'name' => 'Bagua Grande'],
            ['state_id' => $states['PE_AMAZONAS'], 'name' => 'Jazán'],
            ['state_id' => $states['PE_AMAZONAS'], 'name' => 'Rodríguez de Mendoza'],

// Áncash
            ['state_id' => $states['PE_ANCASH'], 'name' => 'Huaraz'],
            ['state_id' => $states['PE_ANCASH'], 'name' => 'Chimbote'],
            ['state_id' => $states['PE_ANCASH'], 'name' => 'Nuevo Chimbote'],
            ['state_id' => $states['PE_ANCASH'], 'name' => 'Caraz'],
            ['state_id' => $states['PE_ANCASH'], 'name' => 'Casma'],
            ['state_id' => $states['PE_ANCASH'], 'name' => 'Huarmey'],
            ['state_id' => $states['PE_ANCASH'], 'name' => 'Yungay'],
            ['state_id' => $states['PE_ANCASH'], 'name' => 'Recuay'],

// Apurímac
            ['state_id' => $states['PE_APURIMAC'], 'name' => 'Abancay'],
            ['state_id' => $states['PE_APURIMAC'], 'name' => 'Andahuaylas'],
            ['state_id' => $states['PE_APURIMAC'], 'name' => 'Chalhuanca'],
            ['state_id' => $states['PE_APURIMAC'], 'name' => 'Tambobamba'],
            ['state_id' => $states['PE_APURIMAC'], 'name' => 'Cotabambas'],

// Arequipa
            ['state_id' => $states['PE_AREQUIPA'], 'name' => 'Arequipa'],
            ['state_id' => $states['PE_AREQUIPA'], 'name' => 'Camaná'],
            ['state_id' => $states['PE_AREQUIPA'], 'name' => 'Mollendo'],
            ['state_id' => $states['PE_AREQUIPA'], 'name' => 'Cerro Colorado'],
            ['state_id' => $states['PE_AREQUIPA'], 'name' => 'Yanahuara'],
            ['state_id' => $states['PE_AREQUIPA'], 'name' => 'Majes'],
            ['state_id' => $states['PE_AREQUIPA'], 'name' => 'Aplao'],

// Ayacucho
            ['state_id' => $states['PE_AYACUCHO'], 'name' => 'Ayacucho'],
            ['state_id' => $states['PE_AYACUCHO'], 'name' => 'Huanta'],
            ['state_id' => $states['PE_AYACUCHO'], 'name' => 'Puquio'],
            ['state_id' => $states['PE_AYACUCHO'], 'name' => 'Coracora'],
            ['state_id' => $states['PE_AYACUCHO'], 'name' => 'San Francisco'],

// Cajamarca
            ['state_id' => $states['PE_CAJAMARCA'], 'name' => 'Cajamarca'],
            ['state_id' => $states['PE_CAJAMARCA'], 'name' => 'Jaén'],
            ['state_id' => $states['PE_CAJAMARCA'], 'name' => 'Chota'],
            ['state_id' => $states['PE_CAJAMARCA'], 'name' => 'Cutervo'],
            ['state_id' => $states['PE_CAJAMARCA'], 'name' => 'Celendín'],
            ['state_id' => $states['PE_CAJAMARCA'], 'name' => 'Bambamarca'],
            ['state_id' => $states['PE_CAJAMARCA'], 'name' => 'Cajabamba'],

// Callao
            ['state_id' => $states['PE_CALLAO'], 'name' => 'Callao'],
            ['state_id' => $states['PE_CALLAO'], 'name' => 'La Perla'],
            ['state_id' => $states['PE_CALLAO'], 'name' => 'Bellavista'],
            ['state_id' => $states['PE_CALLAO'], 'name' => 'Ventanilla'],

// Cusco
            ['state_id' => $states['PE_CUSCO'], 'name' => 'Cusco'],
            ['state_id' => $states['PE_CUSCO'], 'name' => 'Sicuani'],
            ['state_id' => $states['PE_CUSCO'], 'name' => 'Quillabamba'],
            ['state_id' => $states['PE_CUSCO'], 'name' => 'Espinar'],
            ['state_id' => $states['PE_CUSCO'], 'name' => 'Urubamba'],
            ['state_id' => $states['PE_CUSCO'], 'name' => 'Calca'],
            ['state_id' => $states['PE_CUSCO'], 'name' => 'Ollantaytambo'],

            // Huancavelica
            ['state_id' => $states['PE_HUANCAVELICA'], 'name' => 'Huancavelica'],
            ['state_id' => $states['PE_HUANCAVELICA'], 'name' => 'Lircay'],
            ['state_id' => $states['PE_HUANCAVELICA'], 'name' => 'Pampas'],
            ['state_id' => $states['PE_HUANCAVELICA'], 'name' => 'Acobamba'],

            // Huánuco
            ['state_id' => $states['PE_HUANUCO'], 'name' => 'Huánuco'],
            ['state_id' => $states['PE_HUANUCO'], 'name' => 'Tingo María'],
            ['state_id' => $states['PE_HUANUCO'], 'name' => 'Amarilis'],
            ['state_id' => $states['PE_HUANUCO'], 'name' => 'Aucayacu'],
            ['state_id' => $states['PE_HUANUCO'], 'name' => 'La Unión'],

            // Ica
            ['state_id' => $states['PE_ICA'], 'name' => 'Ica'],
            ['state_id' => $states['PE_ICA'], 'name' => 'Pisco'],
            ['state_id' => $states['PE_ICA'], 'name' => 'Chincha Alta'],
            ['state_id' => $states['PE_ICA'], 'name' => 'Nazca'],
            ['state_id' => $states['PE_ICA'], 'name' => 'Palpa'],
            ['state_id' => $states['PE_ICA'], 'name' => 'Paracas'],

            // Junín
            ['state_id' => $states['PE_JUNIN'], 'name' => 'Huancayo'],
            ['state_id' => $states['PE_JUNIN'], 'name' => 'Tarma'],
            ['state_id' => $states['PE_JUNIN'], 'name' => 'Jauja'],
            ['state_id' => $states['PE_JUNIN'], 'name' => 'La Oroya'],
            ['state_id' => $states['PE_JUNIN'], 'name' => 'Satipo'],
            ['state_id' => $states['PE_JUNIN'], 'name' => 'Pichanaqui'],

            // La Libertad
            ['state_id' => $states['PE_LA_LIBERTAD'], 'name' => 'Trujillo'],
            ['state_id' => $states['PE_LA_LIBERTAD'], 'name' => 'Chepén'],
            ['state_id' => $states['PE_LA_LIBERTAD'], 'name' => 'Pacasmayo'],
            ['state_id' => $states['PE_LA_LIBERTAD'], 'name' => 'Huamachuco'],
            ['state_id' => $states['PE_LA_LIBERTAD'], 'name' => 'Otuzco'],
            ['state_id' => $states['PE_LA_LIBERTAD'], 'name' => 'Casa Grande'],
            ['state_id' => $states['PE_LA_LIBERTAD'], 'name' => 'Virú'],

            // Lambayeque
            ['state_id' => $states['PE_LAMBAYEQUE'], 'name' => 'Chiclayo'],
            ['state_id' => $states['PE_LAMBAYEQUE'], 'name' => 'Lambayeque'],
            ['state_id' => $states['PE_LAMBAYEQUE'], 'name' => 'Ferreñafe'],
            ['state_id' => $states['PE_LAMBAYEQUE'], 'name' => 'Pimentel'],
            ['state_id' => $states['PE_LAMBAYEQUE'], 'name' => 'Monsefú'],
            ['state_id' => $states['PE_LAMBAYEQUE'], 'name' => 'Tumán'],

            // Lima
            ['state_id' => $states['PE_LIMA'], 'name' => 'Lima'],
            ['state_id' => $states['PE_LIMA'], 'name' => 'Huacho'],
            ['state_id' => $states['PE_LIMA'], 'name' => 'Huaral'],

            // Loreto
            ['state_id' => $states['PE_LORETO'], 'name' => 'Iquitos'],
            ['state_id' => $states['PE_LORETO'], 'name' => 'Nauta'],
            ['state_id' => $states['PE_LORETO'], 'name' => 'Yurimaguas'],

            // Madre de Dios
            ['state_id' => $states['PE_MADRE_DE_DIOS'], 'name' => 'Puerto Maldonado'],

            // Moquegua
            ['state_id' => $states['PE_MOQUEGUA'], 'name' => 'Moquegua'],
            ['state_id' => $states['PE_MOQUEGUA'], 'name' => 'Ilo'],

            // Pasco
            ['state_id' => $states['PE_PASCO'], 'name' => 'Cerro de Pasco'],
            ['state_id' => $states['PE_PASCO'], 'name' => 'Oxapampa'],

            // Piura
            ['state_id' => $states['PE_PIURA'], 'name' => 'Piura'],
            ['state_id' => $states['PE_PIURA'], 'name' => 'Sullana'],
            ['state_id' => $states['PE_PIURA'], 'name' => 'Talara'],
            ['state_id' => $states['PE_PIURA'], 'name' => 'Paita'],

            // Puno
            ['state_id' => $states['PE_PUNO'], 'name' => 'Puno'],
            ['state_id' => $states['PE_PUNO'], 'name' => 'Juliaca'],

            // San Martín
            ['state_id' => $states['PE_SAN_MARTIN'], 'name' => 'Tarapoto'],
            ['state_id' => $states['PE_SAN_MARTIN'], 'name' => 'Moyobamba'],

            // Tacna
            ['state_id' => $states['PE_TACNA'], 'name' => 'Tacna'],

            // Tumbes
            ['state_id' => $states['PE_TUMBES'], 'name' => 'Tumbes'],

            // Ucayali
            ['state_id' => $states['PE_UCAYALI'], 'name' => 'Pucallpa'],

            // 🇲🇽 MÉXICO

// Aguascalientes
            ['state_id' => $states['MX_AGUASCALIENTES'], 'name' => 'Aguascalientes'],
            ['state_id' => $states['MX_AGUASCALIENTES'], 'name' => 'Jesús María'],
            ['state_id' => $states['MX_AGUASCALIENTES'], 'name' => 'Calvillo'],

// Baja California
            ['state_id' => $states['MX_BAJA_CALIFORNIA'], 'name' => 'Tijuana'],
            ['state_id' => $states['MX_BAJA_CALIFORNIA'], 'name' => 'Mexicali'],
            ['state_id' => $states['MX_BAJA_CALIFORNIA'], 'name' => 'Ensenada'],

// Baja California Sur
            ['state_id' => $states['MX_BAJA_CALIFORNIA_SUR'], 'name' => 'La Paz'],
            ['state_id' => $states['MX_BAJA_CALIFORNIA_SUR'], 'name' => 'Los Cabos'],

// Campeche
            ['state_id' => $states['MX_CAMPECHE'], 'name' => 'Campeche'],
            ['state_id' => $states['MX_CAMPECHE'], 'name' => 'Ciudad del Carmen'],

// Chiapas
            ['state_id' => $states['MX_CHIAPAS'], 'name' => 'Tuxtla Gutiérrez'],
            ['state_id' => $states['MX_CHIAPAS'], 'name' => 'Tapachula'],
            ['state_id' => $states['MX_CHIAPAS'], 'name' => 'San Cristóbal de las Casas'],

// Chihuahua
            ['state_id' => $states['MX_CHIHUAHUA'], 'name' => 'Chihuahua'],
            ['state_id' => $states['MX_CHIHUAHUA'], 'name' => 'Ciudad Juárez'],
            ['state_id' => $states['MX_CHIHUAHUA'], 'name' => 'Delicias'],

// Ciudad de México
            ['state_id' => $states['MX_CIUDAD_DE_MEXICO'], 'name' => 'Ciudad de México'],

// Coahuila
            ['state_id' => $states['MX_COAHUILA'], 'name' => 'Saltillo'],
            ['state_id' => $states['MX_COAHUILA'], 'name' => 'Torreón'],
            ['state_id' => $states['MX_COAHUILA'], 'name' => 'Monclova'],

// Colima
            ['state_id' => $states['MX_COLIMA'], 'name' => 'Colima'],
            ['state_id' => $states['MX_COLIMA'], 'name' => 'Manzanillo'],

// Durango
            ['state_id' => $states['MX_DURANGO'], 'name' => 'Durango'],
            ['state_id' => $states['MX_DURANGO'], 'name' => 'Gómez Palacio'],

// Guanajuato
            ['state_id' => $states['MX_GUANAJUATO'], 'name' => 'León'],
            ['state_id' => $states['MX_GUANAJUATO'], 'name' => 'Irapuato'],
            ['state_id' => $states['MX_GUANAJUATO'], 'name' => 'Celaya'],
            ['state_id' => $states['MX_GUANAJUATO'], 'name' => 'Guanajuato'],

// Guerrero
            ['state_id' => $states['MX_GUERRERO'], 'name' => 'Acapulco'],
            ['state_id' => $states['MX_GUERRERO'], 'name' => 'Chilpancingo'],
            ['state_id' => $states['MX_GUERRERO'], 'name' => 'Iguala'],

// Hidalgo
            ['state_id' => $states['MX_HIDALGO'], 'name' => 'Pachuca'],
            ['state_id' => $states['MX_HIDALGO'], 'name' => 'Tulancingo'],

// Jalisco
            ['state_id' => $states['MX_JALISCO'], 'name' => 'Guadalajara'],
            ['state_id' => $states['MX_JALISCO'], 'name' => 'Zapopan'],
            ['state_id' => $states['MX_JALISCO'], 'name' => 'Tlaquepaque'],

// Estado de México
            ['state_id' => $states['MX_ESTADO_DE_MEXICO'], 'name' => 'Toluca'],
            ['state_id' => $states['MX_ESTADO_DE_MEXICO'], 'name' => 'Ecatepec'],
            ['state_id' => $states['MX_ESTADO_DE_MEXICO'], 'name' => 'Naucalpan'],

// Michoacán
            ['state_id' => $states['MX_MICHOACAN'], 'name' => 'Morelia'],
            ['state_id' => $states['MX_MICHOACAN'], 'name' => 'Uruapan'],

// Morelos
            ['state_id' => $states['MX_MORELOS'], 'name' => 'Cuernavaca'],
            ['state_id' => $states['MX_MORELOS'], 'name' => 'Jiutepec'],

// Nayarit
            ['state_id' => $states['MX_NAYARIT'], 'name' => 'Tepic'],

// Nuevo León
            ['state_id' => $states['MX_NUEVO_LEON'], 'name' => 'Monterrey'],
            ['state_id' => $states['MX_NUEVO_LEON'], 'name' => 'San Nicolás'],
            ['state_id' => $states['MX_NUEVO_LEON'], 'name' => 'Guadalupe'],

// Oaxaca
            ['state_id' => $states['MX_OAXACA'], 'name' => 'Oaxaca'],
            ['state_id' => $states['MX_OAXACA'], 'name' => 'Salina Cruz'],

// Puebla
            ['state_id' => $states['MX_PUEBLA'], 'name' => 'Puebla'],
            ['state_id' => $states['MX_PUEBLA'], 'name' => 'Tehuacán'],

// Querétaro
            ['state_id' => $states['MX_QUERETARO'], 'name' => 'Querétaro'],
            ['state_id' => $states['MX_QUERETARO'], 'name' => 'San Juan del Río'],

// Quintana Roo
            ['state_id' => $states['MX_QUINTANA_ROO'], 'name' => 'Cancún'],
            ['state_id' => $states['MX_QUINTANA_ROO'], 'name' => 'Playa del Carmen'],

// San Luis Potosí
            ['state_id' => $states['MX_SAN_LUIS_POTOSI'], 'name' => 'San Luis Potosí'],
            ['state_id' => $states['MX_SAN_LUIS_POTOSI'], 'name' => 'Ciudad Valles'],

            // Sinaloa
            ['state_id' => $states['MX_SINALOA'], 'name' => 'Culiacán'],
            ['state_id' => $states['MX_SINALOA'], 'name' => 'Mazatlán'],

            // Sonora
            ['state_id' => $states['MX_SONORA'], 'name' => 'Hermosillo'],
            ['state_id' => $states['MX_SONORA'], 'name' => 'Ciudad Obregón'],

            // Tabasco
            ['state_id' => $states['MX_TABASCO'], 'name' => 'Villahermosa'],

            // Tamaulipas
            ['state_id' => $states['MX_TAMAULIPAS'], 'name' => 'Reynosa'],
            ['state_id' => $states['MX_TAMAULIPAS'], 'name' => 'Tampico'],

            // Tlaxcala
            ['state_id' => $states['MX_TLAXCALA'], 'name' => 'Tlaxcala'],

            // Veracruz
            ['state_id' => $states['MX_VERACRUZ'], 'name' => 'Veracruz'],
            ['state_id' => $states['MX_VERACRUZ'], 'name' => 'Xalapa'],

            // Yucatán
            ['state_id' => $states['MX_YUCATAN'], 'name' => 'Mérida'],

            // Zacatecas
            ['state_id' => $states['MX_ZACATECAS'], 'name' => 'Zacatecas'],

            // 🇦🇷 ARGENTINA

// Buenos Aires (Provincia)
            ['state_id' => $states['AR_BUENOS_AIRES'], 'name' => 'La Plata'],
            ['state_id' => $states['AR_BUENOS_AIRES'], 'name' => 'Mar del Plata'],
            ['state_id' => $states['AR_BUENOS_AIRES'], 'name' => 'Bahía Blanca'],
            ['state_id' => $states['AR_BUENOS_AIRES'], 'name' => 'Tandil'],
            ['state_id' => $states['AR_BUENOS_AIRES'], 'name' => 'Olavarría'],
            ['state_id' => $states['AR_BUENOS_AIRES'], 'name' => 'Pergamino'],
            ['state_id' => $states['AR_BUENOS_AIRES'], 'name' => 'San Nicolás'],
            ['state_id' => $states['AR_BUENOS_AIRES'], 'name' => 'Junín'],
            ['state_id' => $states['AR_BUENOS_AIRES'], 'name' => 'Morón'],
            ['state_id' => $states['AR_BUENOS_AIRES'], 'name' => 'Quilmes'],

// CABA
            ['state_id' => $states['AR_CABA'], 'name' => 'Buenos Aires'],

// Catamarca
            ['state_id' => $states['AR_CATAMARCA'], 'name' => 'San Fernando del Valle de Catamarca'],
            ['state_id' => $states['AR_CATAMARCA'], 'name' => 'Belén'],
            ['state_id' => $states['AR_CATAMARCA'], 'name' => 'Andalgalá'],

// Chaco
            ['state_id' => $states['AR_CHACO'], 'name' => 'Resistencia'],
            ['state_id' => $states['AR_CHACO'], 'name' => 'Sáenz Peña'],
            ['state_id' => $states['AR_CHACO'], 'name' => 'Villa Ángela'],

// Chubut
            ['state_id' => $states['AR_CHUBUT'], 'name' => 'Comodoro Rivadavia'],
            ['state_id' => $states['AR_CHUBUT'], 'name' => 'Trelew'],
            ['state_id' => $states['AR_CHUBUT'], 'name' => 'Puerto Madryn'],
            ['state_id' => $states['AR_CHUBUT'], 'name' => 'Esquel'],

// Córdoba
            ['state_id' => $states['AR_CORDOBA'], 'name' => 'Córdoba'],
            ['state_id' => $states['AR_CORDOBA'], 'name' => 'Villa Carlos Paz'],
            ['state_id' => $states['AR_CORDOBA'], 'name' => 'Río Cuarto'],
            ['state_id' => $states['AR_CORDOBA'], 'name' => 'Villa María'],
            ['state_id' => $states['AR_CORDOBA'], 'name' => 'San Francisco'],

// Corrientes
            ['state_id' => $states['AR_CORRIENTES'], 'name' => 'Corrientes'],
            ['state_id' => $states['AR_CORRIENTES'], 'name' => 'Goya'],
            ['state_id' => $states['AR_CORRIENTES'], 'name' => 'Paso de los Libres'],

// Entre Ríos
            ['state_id' => $states['AR_ENTRE_RIOS'], 'name' => 'Paraná'],
            ['state_id' => $states['AR_ENTRE_RIOS'], 'name' => 'Concordia'],
            ['state_id' => $states['AR_ENTRE_RIOS'], 'name' => 'Gualeguaychú'],
            ['state_id' => $states['AR_ENTRE_RIOS'], 'name' => 'Colón'],

// Formosa
            ['state_id' => $states['AR_FORMOSA'], 'name' => 'Formosa'],
            ['state_id' => $states['AR_FORMOSA'], 'name' => 'Clorinda'],

// Jujuy
            ['state_id' => $states['AR_JUJUY'], 'name' => 'San Salvador de Jujuy'],
            ['state_id' => $states['AR_JUJUY'], 'name' => 'Palpalá'],

// La Pampa
            ['state_id' => $states['AR_LA_PAMPA'], 'name' => 'Santa Rosa'],
            ['state_id' => $states['AR_LA_PAMPA'], 'name' => 'General Pico'],

// La Rioja
            ['state_id' => $states['AR_LA_RIOJA'], 'name' => 'La Rioja'],
            ['state_id' => $states['AR_LA_RIOJA'], 'name' => 'Chilecito'],

// Mendoza
            ['state_id' => $states['AR_MENDOZA'], 'name' => 'Mendoza'],
            ['state_id' => $states['AR_MENDOZA'], 'name' => 'San Rafael'],
            ['state_id' => $states['AR_MENDOZA'], 'name' => 'Godoy Cruz'],
            ['state_id' => $states['AR_MENDOZA'], 'name' => 'Luján de Cuyo'],

// Misiones
            ['state_id' => $states['AR_MISIONES'], 'name' => 'Posadas'],
            ['state_id' => $states['AR_MISIONES'], 'name' => 'Oberá'],
            ['state_id' => $states['AR_MISIONES'], 'name' => 'Eldorado'],

// Neuquén
            ['state_id' => $states['AR_NEUQUEN'], 'name' => 'Neuquén'],
            ['state_id' => $states['AR_NEUQUEN'], 'name' => 'Cutral Có'],
            ['state_id' => $states['AR_NEUQUEN'], 'name' => 'Zapala'],

// Río Negro
            ['state_id' => $states['AR_RIO_NEGRO'], 'name' => 'Viedma'],
            ['state_id' => $states['AR_RIO_NEGRO'], 'name' => 'San Carlos de Bariloche'],
            ['state_id' => $states['AR_RIO_NEGRO'], 'name' => 'General Roca'],

// Salta
            ['state_id' => $states['AR_SALTA'], 'name' => 'Salta'],
            ['state_id' => $states['AR_SALTA'], 'name' => 'Tartagal'],
            ['state_id' => $states['AR_SALTA'], 'name' => 'Orán'],

// San Juan
            ['state_id' => $states['AR_SAN_JUAN'], 'name' => 'San Juan'],
            ['state_id' => $states['AR_SAN_JUAN'], 'name' => 'Rawson'],

// San Luis
            ['state_id' => $states['AR_SAN_LUIS'], 'name' => 'San Luis'],
            ['state_id' => $states['AR_SAN_LUIS'], 'name' => 'Villa Mercedes'],

// Santa Cruz
            ['state_id' => $states['AR_SANTA_CRUZ'], 'name' => 'Río Gallegos'],
            ['state_id' => $states['AR_SANTA_CRUZ'], 'name' => 'Caleta Olivia'],

// Santa Fe
            ['state_id' => $states['AR_SANTA_FE'], 'name' => 'Santa Fe'],
            ['state_id' => $states['AR_SANTA_FE'], 'name' => 'Rosario'],
            ['state_id' => $states['AR_SANTA_FE'], 'name' => 'Rafaela'],
            ['state_id' => $states['AR_SANTA_FE'], 'name' => 'Venado Tuerto'],

// Santiago del Estero
            ['state_id' => $states['AR_SANTIAGO_DEL_ESTERO'], 'name' => 'Santiago del Estero'],
            ['state_id' => $states['AR_SANTIAGO_DEL_ESTERO'], 'name' => 'La Banda'],

// Tierra del Fuego
            ['state_id' => $states['AR_TIERRA_DEL_FUEGO'], 'name' => 'Ushuaia'],
            ['state_id' => $states['AR_TIERRA_DEL_FUEGO'], 'name' => 'Río Grande'],

// Tucumán
            ['state_id' => $states['AR_TUCUMAN'], 'name' => 'San Miguel de Tucumán'],
            ['state_id' => $states['AR_TUCUMAN'], 'name' => 'Yerba Buena'],

            // 🇨🇱 CHILE

// Arica y Parinacota
            ['state_id' => $states['CL_ARICA_PARINACOTA'], 'name' => 'Arica'],
            ['state_id' => $states['CL_ARICA_PARINACOTA'], 'name' => 'Putre'],

// Tarapacá
            ['state_id' => $states['CL_TARAPACA'], 'name' => 'Iquique'],
            ['state_id' => $states['CL_TARAPACA'], 'name' => 'Alto Hospicio'],
            ['state_id' => $states['CL_TARAPACA'], 'name' => 'Pozo Almonte'],

// Antofagasta
            ['state_id' => $states['CL_ANTOFAGASTA'], 'name' => 'Antofagasta'],
            ['state_id' => $states['CL_ANTOFAGASTA'], 'name' => 'Calama'],
            ['state_id' => $states['CL_ANTOFAGASTA'], 'name' => 'Tocopilla'],

// Atacama
            ['state_id' => $states['CL_ATACAMA'], 'name' => 'Copiapó'],
            ['state_id' => $states['CL_ATACAMA'], 'name' => 'Vallenar'],
            ['state_id' => $states['CL_ATACAMA'], 'name' => 'Caldera'],

// Coquimbo
            ['state_id' => $states['CL_COQUIMBO'], 'name' => 'La Serena'],
            ['state_id' => $states['CL_COQUIMBO'], 'name' => 'Coquimbo'],
            ['state_id' => $states['CL_COQUIMBO'], 'name' => 'Ovalle'],
            ['state_id' => $states['CL_COQUIMBO'], 'name' => 'Illapel'],

// Valparaíso
            ['state_id' => $states['CL_VALPARAISO'], 'name' => 'Valparaíso'],
            ['state_id' => $states['CL_VALPARAISO'], 'name' => 'Viña del Mar'],
            ['state_id' => $states['CL_VALPARAISO'], 'name' => 'Quilpué'],
            ['state_id' => $states['CL_VALPARAISO'], 'name' => 'Villa Alemana'],
            ['state_id' => $states['CL_VALPARAISO'], 'name' => 'San Antonio'],
            ['state_id' => $states['CL_VALPARAISO'], 'name' => 'Quillota'],

// Metropolitana
            ['state_id' => $states['CL_METROPOLITANA'], 'name' => 'Santiago'],
            ['state_id' => $states['CL_METROPOLITANA'], 'name' => 'Puente Alto'],
            ['state_id' => $states['CL_METROPOLITANA'], 'name' => 'Maipú'],
            ['state_id' => $states['CL_METROPOLITANA'], 'name' => 'La Florida'],
            ['state_id' => $states['CL_METROPOLITANA'], 'name' => 'Las Condes'],
            ['state_id' => $states['CL_METROPOLITANA'], 'name' => 'San Bernardo'],

// O'Higgins
            ['state_id' => $states['CL_OHIGGINS'], 'name' => 'Rancagua'],
            ['state_id' => $states['CL_OHIGGINS'], 'name' => 'San Fernando'],
            ['state_id' => $states['CL_OHIGGINS'], 'name' => 'Santa Cruz'],

// Maule
            ['state_id' => $states['CL_MAULE'], 'name' => 'Talca'],
            ['state_id' => $states['CL_MAULE'], 'name' => 'Curicó'],
            ['state_id' => $states['CL_MAULE'], 'name' => 'Linares'],
            ['state_id' => $states['CL_MAULE'], 'name' => 'Constitución'],

// Ñuble
            ['state_id' => $states['CL_NUBLE'], 'name' => 'Chillán'],
            ['state_id' => $states['CL_NUBLE'], 'name' => 'San Carlos'],

// Biobío
            ['state_id' => $states['CL_BIOBIO'], 'name' => 'Concepción'],
            ['state_id' => $states['CL_BIOBIO'], 'name' => 'Talcahuano'],
            ['state_id' => $states['CL_BIOBIO'], 'name' => 'Los Ángeles'],
            ['state_id' => $states['CL_BIOBIO'], 'name' => 'Coronel'],

// La Araucanía
            ['state_id' => $states['CL_ARAUCANIA'], 'name' => 'Temuco'],
            ['state_id' => $states['CL_ARAUCANIA'], 'name' => 'Villarrica'],
            ['state_id' => $states['CL_ARAUCANIA'], 'name' => 'Angol'],

// Los Ríos
            ['state_id' => $states['CL_LOS_RIOS'], 'name' => 'Valdivia'],
            ['state_id' => $states['CL_LOS_RIOS'], 'name' => 'La Unión'],

// Los Lagos
            ['state_id' => $states['CL_LOS_LAGOS'], 'name' => 'Puerto Montt'],
            ['state_id' => $states['CL_LOS_LAGOS'], 'name' => 'Osorno'],
            ['state_id' => $states['CL_LOS_LAGOS'], 'name' => 'Castro'],
            ['state_id' => $states['CL_LOS_LAGOS'], 'name' => 'Ancud'],

// Aysén
            ['state_id' => $states['CL_AYSEN'], 'name' => 'Coyhaique'],
            ['state_id' => $states['CL_AYSEN'], 'name' => 'Puerto Aysén'],

// Magallanes
            ['state_id' => $states['CL_MAGALLANES'], 'name' => 'Punta Arenas'],
            ['state_id' => $states['CL_MAGALLANES'], 'name' => 'Puerto Natales'],

            // 🇻🇪 VENEZUELA

// Amazonas
            ['state_id' => $states['VE_AMAZONAS'], 'name' => 'Puerto Ayacucho'],

// Anzoátegui
            ['state_id' => $states['VE_ANZOATEGUI'], 'name' => 'Barcelona'],
            ['state_id' => $states['VE_ANZOATEGUI'], 'name' => 'Puerto La Cruz'],
            ['state_id' => $states['VE_ANZOATEGUI'], 'name' => 'El Tigre'],
            ['state_id' => $states['VE_ANZOATEGUI'], 'name' => 'Anaco'],

// Apure
            ['state_id' => $states['VE_APURE'], 'name' => 'San Fernando de Apure'],
            ['state_id' => $states['VE_APURE'], 'name' => 'Guasdualito'],

// Aragua
            ['state_id' => $states['VE_ARAGUA'], 'name' => 'Maracay'],
            ['state_id' => $states['VE_ARAGUA'], 'name' => 'Turmero'],
            ['state_id' => $states['VE_ARAGUA'], 'name' => 'La Victoria'],
            ['state_id' => $states['VE_ARAGUA'], 'name' => 'Cagua'],

// Barinas
            ['state_id' => $states['VE_BARINAS'], 'name' => 'Barinas'],

// Bolívar
            ['state_id' => $states['VE_BOLIVAR'], 'name' => 'Ciudad Bolívar'],
            ['state_id' => $states['VE_BOLIVAR'], 'name' => 'Ciudad Guayana'],
            ['state_id' => $states['VE_BOLIVAR'], 'name' => 'Upata'],

// Carabobo
            ['state_id' => $states['VE_CARABOBO'], 'name' => 'Valencia'],
            ['state_id' => $states['VE_CARABOBO'], 'name' => 'Puerto Cabello'],
            ['state_id' => $states['VE_CARABOBO'], 'name' => 'Naguanagua'],

// Cojedes
            ['state_id' => $states['VE_COJEDES'], 'name' => 'San Carlos'],

// Delta Amacuro
            ['state_id' => $states['VE_DELTA_AMACURO'], 'name' => 'Tucupita'],

// Falcón
            ['state_id' => $states['VE_FALCON'], 'name' => 'Coro'],
            ['state_id' => $states['VE_FALCON'], 'name' => 'Punto Fijo'],

// Guárico
            ['state_id' => $states['VE_GUARICO'], 'name' => 'San Juan de los Morros'],
            ['state_id' => $states['VE_GUARICO'], 'name' => 'Calabozo'],

// Lara
            ['state_id' => $states['VE_LARA'], 'name' => 'Barquisimeto'],
            ['state_id' => $states['VE_LARA'], 'name' => 'Cabudare'],
            ['state_id' => $states['VE_LARA'], 'name' => 'Carora'],

// Mérida
            ['state_id' => $states['VE_MERIDA'], 'name' => 'Mérida'],
            ['state_id' => $states['VE_MERIDA'], 'name' => 'El Vigía'],

// Miranda
            ['state_id' => $states['VE_MIRANDA'], 'name' => 'Los Teques'],
            ['state_id' => $states['VE_MIRANDA'], 'name' => 'Guarenas'],
            ['state_id' => $states['VE_MIRANDA'], 'name' => 'Guatire'],
            ['state_id' => $states['VE_MIRANDA'], 'name' => 'Charallave'],

// Monagas
            ['state_id' => $states['VE_MONAGAS'], 'name' => 'Maturín'],
            ['state_id' => $states['VE_MONAGAS'], 'name' => 'Punta de Mata'],

// Nueva Esparta
            ['state_id' => $states['VE_NUEVA_ESPARTA'], 'name' => 'Porlamar'],
            ['state_id' => $states['VE_NUEVA_ESPARTA'], 'name' => 'La Asunción'],

// Portuguesa
            ['state_id' => $states['VE_PORTUGUESA'], 'name' => 'Guanare'],
            ['state_id' => $states['VE_PORTUGUESA'], 'name' => 'Acarigua'],
            ['state_id' => $states['VE_PORTUGUESA'], 'name' => 'Araure'],

// Sucre
            ['state_id' => $states['VE_SUCRE'], 'name' => 'Cumaná'],
            ['state_id' => $states['VE_SUCRE'], 'name' => 'Carúpano'],

// Táchira
            ['state_id' => $states['VE_TACHIRA'], 'name' => 'San Cristóbal'],

// Trujillo
            ['state_id' => $states['VE_TRUJILLO'], 'name' => 'Trujillo'],
            ['state_id' => $states['VE_TRUJILLO'], 'name' => 'Valera'],

// Vargas (La Guaira)
            ['state_id' => $states['VE_VARGAS'], 'name' => 'La Guaira'],

// Yaracuy
            ['state_id' => $states['VE_YARACUY'], 'name' => 'San Felipe'],

// Zulia
            ['state_id' => $states['VE_ZULIA'], 'name' => 'Maracaibo'],
            ['state_id' => $states['VE_ZULIA'], 'name' => 'Cabimas'],
            ['state_id' => $states['VE_ZULIA'], 'name' => 'Ciudad Ojeda'],

// Distrito Capital
            ['state_id' => $states['VE_DISTRITO_CAPITAL'], 'name' => 'Caracas'],


            // 🇧🇴 BOLIVIA

// Beni
            ['state_id' => $states['BO_BENI'], 'name' => 'Trinidad'],
            ['state_id' => $states['BO_BENI'], 'name' => 'Riberalta'],
            ['state_id' => $states['BO_BENI'], 'name' => 'Guayaramerín'],

// Chuquisaca
            ['state_id' => $states['BO_CHUQUISACA'], 'name' => 'Sucre'],
            ['state_id' => $states['BO_CHUQUISACA'], 'name' => 'Monteagudo'],

// Cochabamba
            ['state_id' => $states['BO_COCHABAMBA'], 'name' => 'Cochabamba'],
            ['state_id' => $states['BO_COCHABAMBA'], 'name' => 'Sacaba'],
            ['state_id' => $states['BO_COCHABAMBA'], 'name' => 'Quillacollo'],
            ['state_id' => $states['BO_COCHABAMBA'], 'name' => 'Villa Tunari'],

// La Paz
            ['state_id' => $states['BO_LA_PAZ'], 'name' => 'La Paz'],
            ['state_id' => $states['BO_LA_PAZ'], 'name' => 'El Alto'],
            ['state_id' => $states['BO_LA_PAZ'], 'name' => 'Viacha'],
            ['state_id' => $states['BO_LA_PAZ'], 'name' => 'Achacachi'],

// Oruro
            ['state_id' => $states['BO_ORURO'], 'name' => 'Oruro'],
            ['state_id' => $states['BO_ORURO'], 'name' => 'Huanuni'],

// Pando
            ['state_id' => $states['BO_PANDO'], 'name' => 'Cobija'],

// Potosí
            ['state_id' => $states['BO_POTOSI'], 'name' => 'Potosí'],
            ['state_id' => $states['BO_POTOSI'], 'name' => 'Uyuni'],
            ['state_id' => $states['BO_POTOSI'], 'name' => 'Villazón'],

// Santa Cruz
            ['state_id' => $states['BO_SANTA_CRUZ'], 'name' => 'Santa Cruz de la Sierra'],
            ['state_id' => $states['BO_SANTA_CRUZ'], 'name' => 'Montero'],
            ['state_id' => $states['BO_SANTA_CRUZ'], 'name' => 'Warnes'],
            ['state_id' => $states['BO_SANTA_CRUZ'], 'name' => 'Camiri'],

// Tarija
            ['state_id' => $states['BO_TARIJA'], 'name' => 'Tarija'],
            ['state_id' => $states['BO_TARIJA'], 'name' => 'Yacuiba'],
            ['state_id' => $states['BO_TARIJA'], 'name' => 'Bermejo'],


            // 🇵🇦 PANAMÁ

// Bocas del Toro
            ['state_id' => $states['PA_BOCAS_DEL_TORO'], 'name' => 'Bocas del Toro'],
            ['state_id' => $states['PA_BOCAS_DEL_TORO'], 'name' => 'Changuinola'],
            ['state_id' => $states['PA_BOCAS_DEL_TORO'], 'name' => 'Almirante'],

// Chiriquí
            ['state_id' => $states['PA_CHIRIQUI'], 'name' => 'David'],
            ['state_id' => $states['PA_CHIRIQUI'], 'name' => 'Puerto Armuelles'],
            ['state_id' => $states['PA_CHIRIQUI'], 'name' => 'Boquete'],
            ['state_id' => $states['PA_CHIRIQUI'], 'name' => 'Bugaba'],

// Coclé
            ['state_id' => $states['PA_COCLE'], 'name' => 'Penonomé'],
            ['state_id' => $states['PA_COCLE'], 'name' => 'Aguadulce'],
            ['state_id' => $states['PA_COCLE'], 'name' => 'Natá'],

// Colón
            ['state_id' => $states['PA_COLON'], 'name' => 'Colón'],
            ['state_id' => $states['PA_COLON'], 'name' => 'Portobelo'],
            ['state_id' => $states['PA_COLON'], 'name' => 'Sabanitas'],

// Darién
            ['state_id' => $states['PA_DARIEN'], 'name' => 'La Palma'],
            ['state_id' => $states['PA_DARIEN'], 'name' => 'Yaviza'],

// Herrera
            ['state_id' => $states['PA_HERRERA'], 'name' => 'Chitré'],
            ['state_id' => $states['PA_HERRERA'], 'name' => 'Las Tablas - Herrera'],
            ['state_id' => $states['PA_HERRERA'], 'name' => 'Ocú'],

// Los Santos
            ['state_id' => $states['PA_LOS_SANTOS'], 'name' => 'Las Tablas - Los Santos'],
            ['state_id' => $states['PA_LOS_SANTOS'], 'name' => 'Guararé'],
            ['state_id' => $states['PA_LOS_SANTOS'], 'name' => 'Pedasí'],

// Panamá
            ['state_id' => $states['PA_PANAMA'], 'name' => 'Ciudad de Panamá'],
            ['state_id' => $states['PA_PANAMA'], 'name' => 'San Miguelito'],
            ['state_id' => $states['PA_PANAMA'], 'name' => 'Chepo'],
            ['state_id' => $states['PA_PANAMA'], 'name' => 'Pacora'],

// Panamá Oeste
            ['state_id' => $states['PA_PANAMA_OESTE'], 'name' => 'La Chorrera'],
            ['state_id' => $states['PA_PANAMA_OESTE'], 'name' => 'Arraiján'],
            ['state_id' => $states['PA_PANAMA_OESTE'], 'name' => 'Capira'],

// Veraguas
            ['state_id' => $states['PA_VERAGUAS'], 'name' => 'Santiago'],
            ['state_id' => $states['PA_VERAGUAS'], 'name' => 'Soná'],

// Guna Yala
            ['state_id' => $states['PA_GUNA_YALA'], 'name' => 'El Porvenir'],

// Ngäbe-Buglé
            ['state_id' => $states['PA_NGABE_BUGLE'], 'name' => 'Buabidi'],

// Emberá-Wounaan
            ['state_id' => $states['PA_EMBERA_WOUNAAN'], 'name' => 'Unión Chocó'],

// Guna de Madugandí
            ['state_id' => $states['PA_GUNA_DE_MADUGANDI'], 'name' => 'Ikandi'],

// Guna de Wargandí
            ['state_id' => $states['PA_GUNA_DE_WARGANDI'], 'name' => 'Nurra'],

            // 🇨🇺 CUBA

// La Habana
            ['state_id' => $states['CU_HABANA'], 'name' => 'La Habana'],
            ['state_id' => $states['CU_HABANA'], 'name' => 'Centro Habana'],
            ['state_id' => $states['CU_HABANA'], 'name' => 'Habana Vieja'],
            ['state_id' => $states['CU_HABANA'], 'name' => 'Vedado'],
            ['state_id' => $states['CU_HABANA'], 'name' => 'Playa'],
            ['state_id' => $states['CU_HABANA'], 'name' => 'Marianao'],

// Artemisa
            ['state_id' => $states['CU_ARTEMISA'], 'name' => 'Artemisa'],
            ['state_id' => $states['CU_ARTEMISA'], 'name' => 'San Antonio de los Baños'],
            ['state_id' => $states['CU_ARTEMISA'], 'name' => 'Guanajay'],
            ['state_id' => $states['CU_ARTEMISA'], 'name' => 'Mariel'],

// Mayabeque
            ['state_id' => $states['CU_MAYABEQUE'], 'name' => 'San José de las Lajas'],
            ['state_id' => $states['CU_MAYABEQUE'], 'name' => 'Santa Cruz del Norte'],
            ['state_id' => $states['CU_MAYABEQUE'], 'name' => 'Bejucal'],
            ['state_id' => $states['CU_MAYABEQUE'], 'name' => 'Güines'],

// Pinar del Río
            ['state_id' => $states['CU_PINAR'], 'name' => 'Pinar del Río'],
            ['state_id' => $states['CU_PINAR'], 'name' => 'Viñales'],
            ['state_id' => $states['CU_PINAR'], 'name' => 'San Juan y Martínez'],
            ['state_id' => $states['CU_PINAR'], 'name' => 'Consolación del Sur'],

// Matanzas
            ['state_id' => $states['CU_MATANZAS'], 'name' => 'Matanzas'],
            ['state_id' => $states['CU_MATANZAS'], 'name' => 'Varadero'],
            ['state_id' => $states['CU_MATANZAS'], 'name' => 'Cárdenas'],
            ['state_id' => $states['CU_MATANZAS'], 'name' => 'Colón'],

// Villa Clara
            ['state_id' => $states['CU_VILLA_CLARA'], 'name' => 'Santa Clara'],
            ['state_id' => $states['CU_VILLA_CLARA'], 'name' => 'Caibarién'],
            ['state_id' => $states['CU_VILLA_CLARA'], 'name' => 'Remedios'],
            ['state_id' => $states['CU_VILLA_CLARA'], 'name' => 'Sagua la Grande'],

// Cienfuegos
            ['state_id' => $states['CU_CIENFUEGOS'], 'name' => 'Cienfuegos'],
            ['state_id' => $states['CU_CIENFUEGOS'], 'name' => 'Palmira'],
            ['state_id' => $states['CU_CIENFUEGOS'], 'name' => 'Cruces'],

// Sancti Spíritus
            ['state_id' => $states['CU_SANCTI'], 'name' => 'Sancti Spíritus'],
            ['state_id' => $states['CU_SANCTI'], 'name' => 'Trinidad'],
            ['state_id' => $states['CU_SANCTI'], 'name' => 'Cabaiguán'],

// Ciego de Ávila
            ['state_id' => $states['CU_CIEGO'], 'name' => 'Ciego de Ávila'],
            ['state_id' => $states['CU_CIEGO'], 'name' => 'Morón'],
            ['state_id' => $states['CU_CIEGO'], 'name' => 'Chambas'],

// Camagüey
            ['state_id' => $states['CU_CAMAGUEY'], 'name' => 'Camagüey'],
            ['state_id' => $states['CU_CAMAGUEY'], 'name' => 'Florida'],
            ['state_id' => $states['CU_CAMAGUEY'], 'name' => 'Nuevitas'],
            ['state_id' => $states['CU_CAMAGUEY'], 'name' => 'Santa Cruz del Sur'],

// Las Tunas
            ['state_id' => $states['CU_LAS_TUNAS'], 'name' => 'Las Tunas'],
            ['state_id' => $states['CU_LAS_TUNAS'], 'name' => 'Puerto Padre'],
            ['state_id' => $states['CU_LAS_TUNAS'], 'name' => 'Colombia'],

// Holguín
            ['state_id' => $states['CU_HOLGUIN'], 'name' => 'Holguín'],
            ['state_id' => $states['CU_HOLGUIN'], 'name' => 'Moa'],
            ['state_id' => $states['CU_HOLGUIN'], 'name' => 'Banes'],
            ['state_id' => $states['CU_HOLGUIN'], 'name' => 'Gibara'],

// Granma
            ['state_id' => $states['CU_GRANMA'], 'name' => 'Bayamo'],
            ['state_id' => $states['CU_GRANMA'], 'name' => 'Manzanillo'],
            ['state_id' => $states['CU_GRANMA'], 'name' => 'Niquero'],

// Santiago de Cuba
            ['state_id' => $states['CU_SANTIAGO'], 'name' => 'Santiago de Cuba'],
            ['state_id' => $states['CU_SANTIAGO'], 'name' => 'Palma Soriano'],
            ['state_id' => $states['CU_SANTIAGO'], 'name' => 'Contramaestre'],

// Guantánamo
            ['state_id' => $states['CU_GUANTANAMO'], 'name' => 'Guantánamo'],
            ['state_id' => $states['CU_GUANTANAMO'], 'name' => 'Baracoa'],
            ['state_id' => $states['CU_GUANTANAMO'], 'name' => 'Maisí'],

// Isla de la Juventud
            ['state_id' => $states['CU_ISLA_JUVENTUD'], 'name' => 'Nueva Gerona'],


        ];

        DB::table('cities')->upsert(
            $cities,
            ['state_id', 'name'],
            []
        );
    }
}
