# ChatBot ANJE Formação - WordPress Plugin

Plugin WordPress para o chatbot da ANJE Formação (anjeformacao.pt).

## Funcionalidades

- Chatbot integrado com OpenRouter (LLM)
- Suporte a backend Flask dedicado
- Nome do chatbot personalizável
- Cor principal e posição configuráveis
- Mensagem de boas-vindas personalizável
- Respostas sobre cursos, equipa, órgãos sociais, contactos
- Design responsivo (mobile-friendly)
- Animações suaves
- Sem dependências externas (CSS/JS inline)

## Instalação

1. Fazer upload da pasta `chatbot-anje-formacao` para `/wp-content/plugins/`
2. Ativar o plugin em **Plugins > ChatBot ANJE Formação**
3. Configurar em **Definições > ChatBot ANJE**

## Configuração

### Opção A: Backend Flask (Recomendado)
1. Insira a URL do backend (ex: `https://chat.anjeformacao.pt`)
2. O chatbot proxya os pedidos para o backend

### Opção B: API Direta
1. Insira a OpenRouter API Key
2. O chatbot chama a API diretamente do WordPress

## Personalização

- **Nome do ChatBot**: Nome mostrado no cabeçalho
- **Cor Principal**: Cor do botão e header
- **Posição**: Direita ou esquerda
- **Mensagem de Boas-vindas**: Texto inicial do chat

## Estrutura

```
chatbot-anje-formacao/
├── chatbot-anje-formacao.php    # Plugin principal
├── includes/
│   └── class-chatbot-anje.php   # Classe principal
├── assets/
│   └── css/
│       └── chatbot-anje.css     # Estilos (placeholder)
└── README.md
```

## Versão

1.0.0
