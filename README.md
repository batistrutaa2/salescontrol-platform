# 📊 SalesControl - Sistema de Controle de Vendas

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-11-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 11">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.2+">
  <img src="https://img.shields.io/badge/Vite-5.0-646CFF?style=for-the-badge&logo=vite&logoColor=white" alt="Vite">
  <img src="https://img.shields.io/badge/Jetstream-5.1-9333EA?style=for-the-badge&logo=laravel&logoColor=white" alt="Jetstream">
</p>

## 🎯 Sobre o Projeto

O **SalesControl** é um sistema completo de **CRM (Customer Relationship Management)** desenvolvido especificamente para empresas de vendas, corretoras e equipes comerciais que precisam gerenciar todo o ciclo de vendas de forma eficiente e organizada.

### 🚀 Principais Funcionalidades

#### 👥 **Gestão de Contatos e Pessoas**
- Cadastro completo de pessoas físicas e jurídicas
- Gestão de contatos com histórico detalhado
- Sistema de dependentes e relacionamentos
- Controle de corretores e suas carteiras
- Transferência de contatos entre corretores

#### 💼 **Sistema Comercial Avançado**
- Controle completo de vendas e negociações
- Ranking de vendedores em tempo real
- Agendamentos e reuniões comerciais
- Sistema de metas e configurações personalizáveis
- Tabulações e classificações de leads

#### 📞 **Central de Relacionamento**
- Sistema integrado de ligações (PABX)
- Controle de ramais e discadores
- Histórico completo de interações
- Sistema preditivo de ligações
- Comentários e anotações detalhadas

#### 📈 **Relatórios e Analytics**
- Relatórios gerenciais completos
- Dashboards interativos
- Análise de performance de vendedores
- Métricas de conversão e produtividade
- Exportação de dados em Excel

#### 🔗 **Integrações Externas**
- API Lemit para consultas de CPF/CNPJ
- Importação de dados via Excel
- Sistema de notificações
- Integrações com operadoras

## 🛠️ Tecnologias Utilizadas

### **Backend**
- **Laravel 11** - Framework PHP moderno e robusto
- **PHP 8.2+** - Linguagem de programação
- **Laravel Jetstream** - Autenticação e gerenciamento de equipes
- **Laravel Sanctum** - Autenticação de API
- **Eloquent ORM** - Mapeamento objeto-relacional

### **Frontend**
- **Materio Template** - Interface moderna e responsiva
- **Vite** - Build tool e bundler
- **JavaScript ES6+** - Interatividade do frontend
- **CSS/SCSS** - Estilização avançada

### **Banco de Dados**
- **MySQL/PostgreSQL** - Banco de dados relacional
- **Migrations** - Controle de versão do banco
- **Seeders** - Dados iniciais do sistema

### **Ferramentas de Desenvolvimento**
- **Docker** - Containerização
- **Composer** - Gerenciador de dependências PHP
- **NPM/Yarn** - Gerenciador de dependências JavaScript
- **ESLint/Prettier** - Padronização de código

## 🏗️ Arquitetura do Sistema

O projeto segue os princípios de **Clean Architecture** e **SOLID**, garantindo:

```
app/
├── Http/Controllers/     # Controladores organizados por módulos
├── Models/              # Modelos Eloquent
├── Repositories/        # Camada de abstração de dados
├── UseCases/           # Lógica de negócio
├── Enums/              # Enumerações e constantes
├── Imports/            # Classes de importação
├── Notifications/      # Sistema de notificações
└── Helpers/            # Funções auxiliares
```

### **Principais Módulos**

- **Autenticação**: Login, logout e controle de acesso
- **Comercial**: Vendas, consultas e reuniões
- **Backoffice**: Operações administrativas
- **Manager**: Gestão de usuários e empresas
- **PABX**: Sistema de ligações
- **Relatórios**: Analytics e dashboards
- **Mailing**: Comunicação e campanhas

## ⚙️ Instalação e Configuração

### **Pré-requisitos**
- PHP 8.2 ou superior
- Composer
- Node.js e NPM/Yarn
- MySQL ou PostgreSQL
- Docker (opcional)

### **Instalação**

1. **Clone o repositório**
```bash
git clone [URL_DO_REPOSITORIO]
cd salescontrol-new
```

2. **Instale as dependências PHP**
```bash
composer install
```

3. **Instale as dependências JavaScript**
```bash
npm install
# ou
yarn install
```

4. **Configure o ambiente**
```bash
cp .env.example .env
php artisan key:generate
```

5. **Configure o banco de dados**
- Edite o arquivo `.env` com suas credenciais
- Execute as migrations:
```bash
php artisan migrate
php artisan db:seed
```

6. **Compile os assets**
```bash
npm run build
# Para desenvolvimento:
npm run dev
```

7. **Inicie o servidor**
```bash
php artisan serve
```

### **Docker (Alternativa)**
```bash
docker-compose up -d
```

## 🚀 Como Usar

1. **Acesse o sistema** através do navegador
2. **Faça login** com suas credenciais
3. **Configure sua empresa** e usuários
4. **Importe contatos** ou cadastre manualmente
5. **Configure metas** e tabulações
6. **Inicie o processo comercial**

## 📝 Principais Entidades

- **Pessoa**: Dados pessoais e comerciais
- **Empresa**: Informações corporativas
- **Contatos**: Leads e prospects
- **Vendas**: Negócios fechados
- **Agendamentos**: Reuniões e follow-ups
- **Ligações**: Histórico de chamadas
- **Comentários**: Anotações e observações
- **Usuários**: Corretores e administradores

## 🤝 Contribuição

Para contribuir com o projeto:

1. Faça um fork do repositório
2. Crie uma branch para sua feature (`git checkout -b feature/nova-funcionalidade`)
3. Commit suas mudanças (`git commit -am 'Adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

## 📞 Suporte

Para suporte técnico ou dúvidas sobre o sistema, entre em contato através dos canais oficiais da empresa.

---

**Desenvolvido com ❤️ para otimizar processos comerciais e impulsionar vendas.**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
