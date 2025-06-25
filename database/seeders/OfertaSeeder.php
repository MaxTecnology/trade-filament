<?php

namespace Database\Seeders;

use App\Models\Oferta;
use App\Models\Usuario;
use App\Models\Categoria;
use App\Models\Subcategoria;
use Illuminate\Database\Seeder;

class OfertaSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = Usuario::where('id', '>', 1)->get(); // Excluir matriz
        $categorias = Categoria::all();

        $ofertas = [
            [
                'titulo' => 'Consulta Odontológica Completa',
                'tipo' => 'servico',
                'descricao' => 'Consulta completa com limpeza, avaliação e orientações preventivas.',
                'valor' => 150.00,
                'categoria' => 'Saúde e Bem-estar',
                'subcategoria' => 'Odontologia',
                'quantidade' => 10,
                'cidade' => 'São Paulo',
                'estado' => 'SP'
            ],
            [
                'titulo' => 'Curso de Inglês - 6 meses',
                'tipo' => 'servico',
                'descricao' => 'Curso completo de inglês básico ao intermediário, com certificado.',
                'valor' => 800.00,
                'categoria' => 'Educação',
                'subcategoria' => 'Idiomas',
                'quantidade' => 20,
                'cidade' => 'Rio de Janeiro',
                'estado' => 'RJ'
            ],
            [
                'titulo' => 'Pizza Família Grande',
                'tipo' => 'produto',
                'descricao' => 'Pizza família grande com 8 fatias, sabores variados disponíveis.',
                'valor' => 45.00,
                'categoria' => 'Alimentação',
                'subcategoria' => 'Restaurantes',
                'quantidade' => 50,
                'cidade' => 'Belo Horizonte',
                'estado' => 'MG'
            ],
            [
                'titulo' => 'Desenvolvimento de Site',
                'tipo' => 'servico',
                'descricao' => 'Desenvolvimento de site responsivo com até 5 páginas.',
                'valor' => 2500.00,
                'categoria' => 'Tecnologia',
                'subcategoria' => 'Desenvolvimento',
                'quantidade' => 5,
                'cidade' => 'Porto Alegre',
                'estado' => 'RS'
            ],
            [
                'titulo' => 'Sofá 3 Lugares',
                'tipo' => 'produto',
                'descricao' => 'Sofá confortável de 3 lugares, tecido resistente, várias cores.',
                'valor' => 1200.00,
                'categoria' => 'Casa e Decoração',
                'subcategoria' => 'Móveis',
                'quantidade' => 3,
                'cidade' => 'Curitiba',
                'estado' => 'PR'
            ],
            [
                'titulo' => 'Sessão de Fisioterapia',
                'tipo' => 'servico',
                'descricao' => 'Sessão individual de fisioterapia com profissional especializado.',
                'valor' => 80.00,
                'categoria' => 'Saúde e Bem-estar',
                'subcategoria' => 'Fisioterapia',
                'quantidade' => 30,
                'cidade' => 'São Paulo',
                'estado' => 'SP'
            ],
            [
                'titulo' => 'Jantar Romântico para 2',
                'tipo' => 'servico',
                'descricao' => 'Jantar especial para casal com entrada, prato principal e sobremesa.',
                'valor' => 180.00,
                'categoria' => 'Alimentação',
                'subcategoria' => 'Restaurantes',
                'quantidade' => 15,
                'cidade' => 'Rio de Janeiro',
                'estado' => 'RJ'
            ],
            [
                'titulo' => 'Tênis Esportivo',
                'tipo' => 'produto',
                'descricao' => 'Tênis esportivo de alta qualidade, várias numerações disponíveis.',
                'valor' => 320.00,
                'categoria' => 'Moda e Beleza',
                'subcategoria' => 'Calçados',
                'quantidade' => 25,
                'cidade' => 'Belo Horizonte',
                'estado' => 'MG'
            ]
        ];

        foreach ($ofertas as $ofertaData) {
            $categoria = $categorias->where('nome_categoria', $ofertaData['categoria'])->first();
            $subcategoria = Subcategoria::where('categoria_id', $categoria->id)
                ->where('nome_subcategoria', $ofertaData['subcategoria'])
                ->first();

            $usuario = $usuarios->random();

            Oferta::create([
                'titulo' => $ofertaData['titulo'],
                'tipo' => $ofertaData['tipo'],
                'status' => true,
                'descricao' => $ofertaData['descricao'],
                'quantidade' => $ofertaData['quantidade'],
                'valor' => $ofertaData['valor'],
                'limite_compra' => $ofertaData['valor'] * 0.8, // 80% do valor
                'vencimento' => now()->addDays(rand(30, 90)),
                'cidade' => $ofertaData['cidade'],
                'estado' => $ofertaData['estado'],
                'retirada' => rand(0, 1) ? 'local' : 'entrega',
                'obs' => 'Oferta válida por tempo limitado.',
                'imagens' => [],
                'usuario_id' => $usuario->id,
                'nome_usuario' => $usuario->nome,
                'categoria_id' => $categoria->id,
                'subcategoria_id' => $subcategoria->id,
            ]);
        }
    }
}
