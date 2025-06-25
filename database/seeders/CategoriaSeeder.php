<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Subcategoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            [
                'nome_categoria' => 'Alimentação',
                'tipo_categoria' => 'produto',
                'subcategorias' => [
                    'Restaurantes',
                    'Padarias',
                    'Lanchonetes',
                    'Delivery',
                    'Doces e Sobremesas'
                ]
            ],
            [
                'nome_categoria' => 'Saúde e Bem-estar',
                'tipo_categoria' => 'servico',
                'subcategorias' => [
                    'Clínicas Médicas',
                    'Odontologia',
                    'Fisioterapia',
                    'Psicologia',
                    'Estética'
                ]
            ],
            [
                'nome_categoria' => 'Educação',
                'tipo_categoria' => 'servico',
                'subcategorias' => [
                    'Cursos Técnicos',
                    'Idiomas',
                    'Reforço Escolar',
                    'Ensino Superior',
                    'Cursos Online'
                ]
            ],
            [
                'nome_categoria' => 'Tecnologia',
                'tipo_categoria' => 'ambos',
                'subcategorias' => [
                    'Desenvolvimento',
                    'Suporte Técnico',
                    'Hardware',
                    'Software',
                    'Consultoria'
                ]
            ],
            [
                'nome_categoria' => 'Casa e Decoração',
                'tipo_categoria' => 'produto',
                'subcategorias' => [
                    'Móveis',
                    'Decoração',
                    'Jardim',
                    'Construção',
                    'Eletrodomésticos'
                ]
            ],
            [
                'nome_categoria' => 'Moda e Beleza',
                'tipo_categoria' => 'produto',
                'subcategorias' => [
                    'Roupas Femininas',
                    'Roupas Masculinas',
                    'Calçados',
                    'Acessórios',
                    'Cosméticos'
                ]
            ],
            [
                'nome_categoria' => 'Automóveis',
                'tipo_categoria' => 'ambos',
                'subcategorias' => [
                    'Carros',
                    'Motos',
                    'Peças',
                    'Serviços',
                    'Seguros'
                ]
            ],
            [
                'nome_categoria' => 'Turismo e Lazer',
                'tipo_categoria' => 'servico',
                'subcategorias' => [
                    'Hotéis',
                    'Pousadas',
                    'Agências de Viagem',
                    'Entretenimento',
                    'Esportes'
                ]
            ]
        ];

        foreach ($categorias as $categoriaData) {
            $categoria = Categoria::create([
                'nome_categoria' => $categoriaData['nome_categoria'],
                'tipo_categoria' => $categoriaData['tipo_categoria'],
            ]);

            foreach ($categoriaData['subcategorias'] as $subcategoriaNome) {
                Subcategoria::create([
                    'nome_subcategoria' => $subcategoriaNome,
                    'categoria_id' => $categoria->id,
                ]);
            }
        }
    }
}
