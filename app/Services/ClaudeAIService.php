<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeAIService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.anthropic.com/v1';
    private string $model = 'claude-3-haiku-20240307'; // Modelo mais econômico

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key') ?? '';
    }

    /**
     * Analisa os comentários de um cliente e sugere estratégias de venda
     */
    public function analisarClienteESugerirEstrategias(array $cliente, array $comentarios): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => 'API Key da Anthropic não configurada. Adicione ANTHROPIC_API_KEY no arquivo .env'
            ];
        }

        // Preparar contexto do cliente
        $contextoCliente = $this->prepararContextoCliente($cliente);

        // Preparar histórico de comentários
        $historicoComentarios = $this->prepararHistoricoComentarios($comentarios);

        // Construir o prompt
        $systemPrompt = $this->getSystemPrompt();
        $userPrompt = $this->getUserPrompt($contextoCliente, $historicoComentarios);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->timeout(30)->post("{$this->baseUrl}/messages", [
                'model' => $this->model,
                'max_tokens' => 1024,
                'system' => $systemPrompt,
                'messages' => [
                    ['role' => 'user', 'content' => $userPrompt]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $content = $data['content'][0]['text'] ?? '';

                return [
                    'success' => true,
                    'sugestoes' => $content,
                    'tokens_usados' => [
                        'input' => $data['usage']['input_tokens'] ?? 0,
                        'output' => $data['usage']['output_tokens'] ?? 0,
                    ]
                ];
            }

            Log::error('Claude API Error', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Erro na API: ' . ($response->json()['error']['message'] ?? 'Erro desconhecido')
            ];

        } catch (\Exception $e) {
            Log::error('Claude API Exception', ['message' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'Erro de conexão: ' . $e->getMessage()
            ];
        }
    }

    private function prepararContextoCliente(array $cliente): string
    {
        $contexto = "## Dados do Cliente\n";
        $contexto .= "- **Nome**: " . ($cliente['nome_cliente'] ?? 'Não informado') . "\n";

        if (!empty($cliente['plano'])) {
            $contexto .= "- **Plano Atual**: {$cliente['plano']}\n";
        }
        if (!empty($cliente['valor_plano_atual'])) {
            $contexto .= "- **Valor Atual**: R$ " . number_format($cliente['valor_plano_atual'], 2, ',', '.') . "\n";
        }
        if (!empty($cliente['categoria'])) {
            $contexto .= "- **Categoria**: {$cliente['categoria']}\n";
        }
        if (!empty($cliente['entidade'])) {
            $contexto .= "- **Entidade**: {$cliente['entidade']}\n";
        }
        if (!empty($cliente['idades'])) {
            $contexto .= "- **Idades dos beneficiários**: {$cliente['idades']}\n";
        }
        if (!empty($cliente['cidade'])) {
            $contexto .= "- **Cidade**: {$cliente['cidade']}\n";
        }

        return $contexto;
    }

    private function prepararHistoricoComentarios(array $comentarios): string
    {
        if (empty($comentarios)) {
            return "Nenhum comentário registrado ainda.";
        }

        $historico = "## Histórico de Interações\n\n";

        foreach ($comentarios as $index => $comentario) {
            $numero = $index + 1;
            $autor = $comentario->name ?? 'Sistema';
            $data = $comentario->created_at ?? 'Data não informada';

            // Limpar HTML do comentário
            $texto = strip_tags($comentario->anotacao ?? '');
            $texto = html_entity_decode($texto);
            $texto = trim($texto);

            if (empty($texto)) continue;

            $historico .= "**[{$numero}] {$data} - {$autor}:**\n";
            $historico .= "{$texto}\n\n";
        }

        return $historico;
    }

    private function getSystemPrompt(): string
    {
        return <<<PROMPT
Você é um especialista em vendas de planos de saúde no Brasil. Sua função é analisar o histórico de interações com um cliente potencial e sugerir estratégias personalizadas para fechar a venda.

## Suas responsabilidades:
1. Analisar o perfil do cliente (idade, plano atual, valor, necessidades)
2. Identificar padrões nas interações anteriores (objeções, interesses, preocupações)
3. Detectar o momento ideal de abordagem
4. Sugerir argumentos de venda personalizados
5. Propor próximos passos concretos

## Formato da resposta:
Estruture sua resposta em seções claras:
- **Resumo do Perfil**: Breve análise do cliente
- **Pontos de Atenção**: Objeções ou preocupações identificadas
- **Estratégia Recomendada**: Abordagem sugerida
- **Argumentos de Venda**: 3-5 argumentos personalizados
- **Próximos Passos**: Ações concretas a tomar

Seja direto, prático e focado em resultados. Use linguagem profissional em português brasileiro.
PROMPT;
    }

    private function getUserPrompt(string $contextoCliente, string $historicoComentarios): string
    {
        return <<<PROMPT
Analise as informações abaixo e sugira estratégias para fechar a venda com este cliente:

{$contextoCliente}

{$historicoComentarios}

Com base nessas informações, forneça uma análise estratégica completa para ajudar o vendedor a converter este lead em cliente.
PROMPT;
    }

    /**
     * Verifica se a API está configurada e funcionando
     */
    public function verificarConexao(): array
    {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'error' => 'API Key não configurada'
            ];
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ])->timeout(10)->post("{$this->baseUrl}/messages", [
                'model' => $this->model,
                'max_tokens' => 10,
                'messages' => [
                    ['role' => 'user', 'content' => 'Teste de conexão. Responda apenas: OK']
                ]
            ]);

            return [
                'success' => $response->successful(),
                'status' => $response->status()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
