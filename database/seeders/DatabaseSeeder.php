<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\AltaInventario;
use App\Models\CatClasificacion;
use App\Models\CatEnfermedades;
use App\Models\CatMarca;
use App\Models\CatTipoFlor;
use App\Models\Clientes;
use App\Models\Empresa;
use App\Models\EnfermedadesTipoFlor;
use App\Models\Producto;
use App\Models\ProductoVenta;
use App\Models\SolucionEnfermedad;
use App\Models\Sucursales;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Empresa::factory()->create([
            'nombre' => 'Pixka',
            'direccion' => 'Calle dos de marzo s/n, San Lucas, Villa Guerrero.',
            'telefono' => '7228259581',
            'email' => 'pixkaconsultores@gmail.com',
            'rfc' => 'JIDE930407AS4',
            'aviso' => 'Si tiene algun requerimiento contactenos por whatsapp',
            'numero_sucursales' => 1000
        ]);

        Sucursales::factory()->create([
            'nombre' => 'Matriz',
            'direccion' => 'Calle dos de marzo s/n, San Lucas, Villa Guerrero.',
            'telefono' => '7228259581',
            'email' => 'pixkaconsultores@gmail.com',
            'es_matriz' => true,
            'id_empresa' => 1
        ]);

        User::factory()->create([
            'name' => 'Erik Jimenez',
            'email' => 'erikedu5@gmail.com',
            'password' => bcrypt('Admin1234.'),
            'tipo' => 'superAdmin',
            'id_sucursal' => 1

       ]);

       User::factory()->create([
            'name' => 'Jesus Casstro',
            'email' => 'jesfirewall@gmail.com',
            'password' => bcrypt('Admin1234.'),
            'tipo' => 'superAdmin',
            'id_sucursal' => 1

       ]);

       CatClasificacion::factory()->create([
            'nombre' => 'Herbicida',
            'criterio' => 'Función'
       ]);

       CatClasificacion::factory()->create([
            'nombre' => 'Fungicida',
            'criterio' => 'Función'
       ]);

       CatClasificacion::factory()->create([
            'nombre' => 'Acaricida',
            'criterio' => 'Función'
       ]);

        CatClasificacion::factory()->create([
            'nombre' => 'Insecticidas',
            'criterio' => 'Función'
        ]);

        CatClasificacion::factory()->create([
            'nombre' => 'Nematicidas',
            'criterio' => 'Función'
        ]);

        CatClasificacion::factory()->create([
            'nombre' => 'Rodenticidas',
            'criterio' => 'Función'
        ]);

        CatClasificacion::factory()->create([
            'nombre' => 'Fertilizantes',
            'criterio' => 'Función'
        ]);

        CatClasificacion::factory()->create([
            'nombre' => 'Fitorreguladores',
            'criterio' => 'Función'
        ]);

        CatClasificacion::factory()->create([
            'nombre' => 'Bactericida',
            'criterio' => 'Función'
        ]);

        CatEnfermedades::factory()->create([
            'nombre' => 'Botrytis',
            'descripcion'=> 'La podredumbre de Botrytis es una infección provocada por hongos en la que, con el tiempo, se puede apreciar al hongo que la causa y no solo los síntomas de la enfermedad latente, ya que las esporas de hongos que parecen un polvo gris se desarrollan sobre el tejido muerto o agonizante de las plantas y se les puede observar fácilmente.'
        ]);

        CatEnfermedades::factory()->create([
            'nombre' => 'Cenicilla',
            'descripcion'=> 'Las cenicillas, también llamadas cenicillas polvorientas o mildiu polvorientos, son causadas por un grupo de hongos diversos, complejos en su forma, en sus estructuras reproductivas, rango de hospedantes y distribución geográfica y producen ascocarpos esféricos llamados casmotecios (previamente denominados cleistotecios) así como conidióforos e hifas hialinas, septadas uninucleadas, y conidios que al desarrollarse en grandes cantidades sobre las superficies afectadas de la planta forman un polvillo blanco a manera de ceniza, lo que las hace fáciles de reconocer.'
        ]);

        CatEnfermedades::factory()->create([
            'nombre' => 'Peronospora',
            'descripcion'=> 'Peronospora sparsa es un patógeno biótrofo o parásito obligado que forma parte de los Oomycetes, los cuales son organismos miceliares semejantes a los hongos, que se conocen comúnmente como mohos acuáticos e incluyen saprófitos y patógenos de plantas, insectos, crustáceos, peces, animales vertebrados y de otros microorganismos. Uno de los aspectos más estudiados en los Oomycetes en los últimos años ha sido sus relaciones filogenéticas intra e intergenéricas'
        ]);
        CatEnfermedades::factory()->create([
            'nombre' => 'Minador de hojas',
            'descripcion'=> 'Se trata de un díptero cuyas larvas se alimentan del parénquima foliar, teniendo preferencia por el haz de las hojas y dejando a su paso galerías serpenteantes sobre las mismas. Finalmente, estas galerías se necrosan. La hembra adulta también provoca daños en las hojas al depositar sus huevos sobre las mismas, dando lugar a puntos blanquecinos.'
        ]);
        CatEnfermedades::factory()->create([
            'nombre' => 'Trips',
            'descripcion'=> 'Esta plaga habita principalmente sobre botones florales y hojas jóvenes, y más raramente sobre hojas senescentes. Los síntomas que se presentan son manchas de aspecto plateado-plomizo rodeadas de motitas negras que se corresponden a sus excrementos.'
        ]);
        CatEnfermedades::factory()->create([
            'nombre' => 'Mosca blanca',
            'descripcion'=> 'Se trata de una plaga que provoca daños en los tejidos, preferiblemente jóvenes, al succionar la savia para su alimentación, y también al ovipositar. Además, originan daños indirectos al segregar una sustancia azucarada donde se instala el hongo negrilla.'
        ]);
        CatEnfermedades::factory()->create([
            'nombre' => 'Araña roja',
            'descripcion'=> 'Se presenta principalmente si el ambiente es seco. Los síntomas que aparecen son unos puntitos de color amarillo en el haz de las hojas y a lo largo de los nervios principales. Posteriormente, estas punteaduras se tornan de color marrón y se abarquillan, obteniendo un aspecto polvoriento. Finalmente, dichas hojas se desecan y caen. Es frecuente encontrar finas telarañas en el envés de las hojas afectadas.'
        ]);
        CatEnfermedades::factory()->create([
            'nombre' => 'Ácaros',
            'descripcion'=> 'Son conocidos como ácaros blancos. Éstos originan daños al realizar sus puestas sobre las hojas jóvenes del centro de la planta y en los botones florales. Las larvas provocan deformaciones en las lígulas, torsiones de la flor y reducción de su desarrollo perimetral (el grado de deformación depende de la densidad poblacional). En las hojas pueden ocasionar deformaciones de los bordes del limbo, plegamiento hacia el haz o el envés de la superficie foliar, engrosamiento del limbo y carácter quebradizo del mismo.'
        ]);
        CatEnfermedades::factory()->create([
            'nombre' => 'Orugas',
            'descripcion'=> 'Estas diferentes especies de noctuidos aparecen con mayor frecuencia durante los meses cálidos, coincidiendo con el aumento de las poblaciones de estos insectos. Las larvas de estas plagas son muy voraces, ocasionando importantes daños sobre las hojas de la planta como consecuencia de su alimentación. En caso de fuertes infecciones, éstos pueden provocar daños también en las flores.'
        ]);
        CatEnfermedades::factory()->create([
            'nombre' => 'Verticilosis',
            'descripcion'=> 'Verticillium dahliae es un hongo propio de épocas invernales. La verticilosis es una enfermedad que provoca la obstrucción de los nervios de las hojas. Los síntomas se manifiestan como un marchitamiento general de la planta, acompañado del amarillamiento progresivo de sus hojas y la decoloración de sus nervios, los cuales terminan por secarse. Finalmente, la planta muere.'
        ]);
        CatEnfermedades::factory()->create([
            'nombre' => 'Podredumbre gris',
            'descripcion'=> 'Este hongo necesita tejidos heridos o senescentes para afectar a la planta, así como humedad ambiental y temperatura elevada. Su desarrollo se inicia sobre material senescente y en descomposición. De éste se traslada a las hojas y flores en donde produce los daños más importantes. Puede causar podredumbre de las plántulas (damping-off), marchitamiento de hojas y flores y podredumbre de la corona. En las hojas pueden aparecer lesiones marrones y en los pétalos de las flores manchas, necrosis de las puntas o marchitamiento completo. Cuando afecta a las lígulas, se denota la formación de pequeñas manchas grisáceas sobre su superficie, afectando a la posterior comercialización de estas flores, ya que el hongo continúa su evolución.'
        ]);
        CatEnfermedades::factory()->create([
            'nombre' => 'Bacteria',
            'descripcion'=> 'Comienza como manchas amarillentas en el tallo de la planta, y se convierte en pudrición de color negro'
        ]);

        CatMarca::factory()->create([
            'nombre' => 'Bayer'
        ]);

        CatMarca::factory()->create([
            'nombre' => 'Syngenta'
        ]);

        CatMarca::factory()->create([
            'nombre' => 'Atlantica'
        ]);

        CatMarca::factory()->create([
            'nombre' => 'Adama'
        ]);

        CatMarca::factory()->create([
            'nombre' => 'Coda'
        ]);

        CatMarca::factory()->create([
            'nombre' => 'Dupont'
        ]);

        CatMarca::factory()->create([
            'nombre' => 'Lapisa'
        ]);

        CatMarca::factory()->create([
            'nombre' => 'Rotam'
        ]);

        CatMarca::factory()->create([
            'nombre' => 'Agrocience'
        ]);

        CatMarca::factory()->create([
            'nombre' => 'Basf'
        ]);
        CatMarca::factory()->create([
            'nombre' => 'FMC'
        ]);
        CatMarca::factory()->create([
            'nombre' => 'Mazfer'
        ]);
        CatMarca::factory()->create([
            'nombre' => 'Monsanto'
        ]);
        CatMarca::factory()->create([
            'nombre' => 'SQM'
        ]);
        CatMarca::factory()->create([
            'nombre' => 'UPL'
        ]);

        CatTipoFlor::factory()->create([
            'nombre' => 'General'
        ]);

        CatTipoFlor::factory()->create([
            'nombre' => 'Enraizada'
        ]);

        CatTipoFlor::factory()->create([
            'nombre' => 'Orientales'
        ]);

        CatTipoFlor::factory()->create([
            'nombre' => 'Rosa'
        ]);

        CatTipoFlor::factory()->create([
            'nombre' => 'Gerbera'
        ]);

        CatTipoFlor::factory()->create([
            'nombre' => 'Gladiola'
        ]);

        CatTipoFlor::factory()->create([
            'nombre' => 'Aster'
        ]);

        CatTipoFlor::factory()->create([
            'nombre' => 'Solidago'
        ]);

        CatTipoFlor::factory()->create([
            'nombre' => 'Astromelia'
        ]);

        CatTipoFlor::factory()->create([
            'nombre' => 'Gipsofilia'
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 1,
            'id_enfermedad' => 1,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 1,
            'id_enfermedad' => 2,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 1,
            'id_enfermedad' => 3,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 1,
            'id_enfermedad' => 4,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 1,
            'id_enfermedad' => 5,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 1,
            'id_enfermedad' => 6,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 1,
            'id_enfermedad' => 7,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 1,
            'id_enfermedad' => 8,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 1,
            'id_enfermedad' => 9,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 1,
            'id_enfermedad' => 10,
        ]);


        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 2,
            'id_enfermedad' => 1,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 2,
            'id_enfermedad' => 2,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 2,
            'id_enfermedad' => 3,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 2,
            'id_enfermedad' => 4,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 2,
            'id_enfermedad' => 5,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 2,
            'id_enfermedad' => 6,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 2,
            'id_enfermedad' => 7,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 2,
            'id_enfermedad' => 8,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 2,
            'id_enfermedad' => 9,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 2,
            'id_enfermedad' => 10,
        ]);


        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 3,
            'id_enfermedad' => 1,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 3,
            'id_enfermedad' => 2,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 3,
            'id_enfermedad' => 3,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 3,
            'id_enfermedad' => 4,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 3,
            'id_enfermedad' => 5,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 3,
            'id_enfermedad' => 6,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 3,
            'id_enfermedad' => 7,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 3,
            'id_enfermedad' => 8,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 3,
            'id_enfermedad' => 9,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 3,
            'id_enfermedad' => 10,
        ]);


        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 4,
            'id_enfermedad' => 1,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 4,
            'id_enfermedad' => 2,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 4,
            'id_enfermedad' => 3,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 4,
            'id_enfermedad' => 4,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 4,
            'id_enfermedad' => 5,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 4,
            'id_enfermedad' => 6,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 4,
            'id_enfermedad' => 7,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 4,
            'id_enfermedad' => 8,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 4,
            'id_enfermedad' => 9,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 5,
            'id_enfermedad' => 10,
        ]);


        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 5,
            'id_enfermedad' => 1,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 5,
            'id_enfermedad' => 2,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 5,
            'id_enfermedad' => 3,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 5,
            'id_enfermedad' => 4,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 5,
            'id_enfermedad' => 5,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 5,
            'id_enfermedad' => 6,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 5,
            'id_enfermedad' => 7,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 5,
            'id_enfermedad' => 8,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 5,
            'id_enfermedad' => 9,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 5,
            'id_enfermedad' => 10,
        ]);


        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 6,
            'id_enfermedad' => 1,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 6,
            'id_enfermedad' => 2,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 6,
            'id_enfermedad' => 3,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 6,
            'id_enfermedad' => 4,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 6,
            'id_enfermedad' => 5,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 6,
            'id_enfermedad' => 6,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 6,
            'id_enfermedad' => 7,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 6,
            'id_enfermedad' => 8,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 6,
            'id_enfermedad' => 9,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 6,
            'id_enfermedad' => 10,
        ]);


        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 7,
            'id_enfermedad' => 1,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 7,
            'id_enfermedad' => 2,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 7,
            'id_enfermedad' => 3,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 7,
            'id_enfermedad' => 4,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 7,
            'id_enfermedad' => 5,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 7,
            'id_enfermedad' => 6,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 7,
            'id_enfermedad' => 7,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 7,
            'id_enfermedad' => 8,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 7,
            'id_enfermedad' => 9,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 7,
            'id_enfermedad' => 10,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 8,
            'id_enfermedad' => 1,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 8,
            'id_enfermedad' => 2,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 8,
            'id_enfermedad' => 3,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 8,
            'id_enfermedad' => 4,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 8,
            'id_enfermedad' => 5,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 8,
            'id_enfermedad' => 6,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 8,
            'id_enfermedad' => 7,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 8,
            'id_enfermedad' => 8,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 8,
            'id_enfermedad' => 9,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 8,
            'id_enfermedad' => 10,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 9,
            'id_enfermedad' => 1,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 9,
            'id_enfermedad' => 2,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 9,
            'id_enfermedad' => 3,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 9,
            'id_enfermedad' => 4,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 9,
            'id_enfermedad' => 5,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 9,
            'id_enfermedad' => 6,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 9,
            'id_enfermedad' => 7,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 9,
            'id_enfermedad' => 8,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 9,
            'id_enfermedad' => 9,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 9,
            'id_enfermedad' => 10,
        ]);


        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 1,
            'id_enfermedad' => 1,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 10,
            'id_enfermedad' => 2,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 10,
            'id_enfermedad' => 3,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 10,
            'id_enfermedad' => 4,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 10,
            'id_enfermedad' => 5,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 10,
            'id_enfermedad' => 6,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 10,
            'id_enfermedad' => 7,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 10,
            'id_enfermedad' => 8,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 10,
            'id_enfermedad' => 9,
        ]);

        EnfermedadesTipoFlor::factory()->create([
            'id_tipo_flor' => 10,
            'id_enfermedad' => 10,
        ]);

    }
}
