# CodeIgniter 4 Module Development Tools

[![Version](https://img.shields.io/badge/version-1.5.1-blue.svg)](https://github.com/rahpt/ci4-module-tools)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%3E%3D8.1-brightgreen.svg)](https://php.net)

Ferramentas de desenvolvimento para o sistema modular CodeIgniter 4. Inclui marketplace de módulos, gerenciador de configurações, automação de ciclo de vida e geradores de código.

---

## 📋 Características

- ✅ **Module Marketplace** - Interface web para gerenciar módulos.
- ✅ **Settings Manager** - Gerenciador centralizado de configurações para todos os módulos.
- ✅ **Lifecycle Automation** - Execução automática de hooks (`install`, `uninstall`).
- ✅ **Home Modularization** - Converter o projeto base em um sistema modular com auto-ativação.
- ✅ **Hot-namespace Registration** - Registro imediato de namespaces para execução de migrações sem restart.
- ✅ **CLI Generators** - Criação rápida de módulos, controladores, migrações e seeders.

---

## 🚀 Comandos CLI

O pacote adiciona diversos comandos ao `php spark` para agilizar o desenvolvimento:

### Gerenciamento de Módulos
- `module:init-core`: Inicializa os módulos base (Dashboard e gerenciador de módulos).
- `module:init <Name>`: Cria um novo módulo com estrutura completa (CRUD, Migration, View).
- `module:modularize-home`: Transforma o controlador `Home.php` e a view `welcome_message.php` em um módulo modular funcional com auto-ativação.
- `module:list`: Lista todos os módulos instalados e seus respectivos status.

### Ciclo de Vida e Instalação
- `module:install <Name>`: Instala um módulo do repositório local ou via URL.
- `module:enable/disable <Name>`: Ativa ou desativa um módulo sem removê-lo.
- `module:publish <Name>`: Prepara e publica os assets de um módulo para a pasta pública.
- `module:assets <Name>`: Gerencia a sincronização de arquivos estáticos.

---

## 🚀 Novidades na v1.4.0

### Automação Inteligente de Views
O mecanismo de modularização da Home agora utiliza expressões regulares avançadas para injetar snippets de autenticação (Login/Logout/Dashboard) sem quebrar o layout original, preservando a navegação existente.

### Ciclo de Vida Completo
O comando `module:modularize-home` agora realiza a ativação automática do módulo, gerenciando as rotas da aplicação central instantaneamente.

---

## 🚀 Instalação

```bash
composer require rahpt/ci4-module-tools
```

### Configuração

**`app/Config/ModuleTools.php`**:
```php
<?php

namespace Config;

use Rahpt\Ci4ModuleTools\Config\ModuleTools as BaseModuleTools;

class ModuleTools extends BaseModuleTools
{
    // Path do repositório local de módulos
    public string $localRepository = APPPATH . 'Modules';
    
    // Modo debug para log de desenvolvimento
    public bool $debugMode = true;
}
```

---

## 📖 Histórico de Versões

### [1.5.1] - 2026-02-26
- **Fix**: Correção crítica no redirecionamento para novos usuários sem UID gerado.
- **Melhoria**: Geração automática de UID no momento do primeiro acesso ao Dashboard.

### [1.5.0] - 2026-02-26
- **Novo**: Lógica de desambiguação inteligente no gerador de Dashboard (`/dashboard` -> `/ID/panel` para não-admins).
- **Melhoria**: Documentação técnica expandida nos módulos gerados sobre fluxo de acesso.

### [1.4.0] - 2026-02-22
- **Novo**: Auto-ativação do módulo Home no comando `module:modularize-home`.
- **Melhoria**: Novo sistema de injeção de views baseado em regex para maior compatibilidade.
- **Otimização**: Refatoração interna do registro de status de módulos.

### [1.3.0] - 2026-02-22
- **Novo**: Comando `module:modularize-home` para conversão de projetos base.
- **Novo**: Comando `module:init-core` para setup inicial de Dashboard e Gerenciador.
- **Melhoria**: Integração profunda com **CodeIgniter Shield**.

### [1.2.0] - 2026-02-18
- **Novo**: Implementado **Settings Manager** para gerenciamento centralizado.
- **Melhoria**: Suporte a Hooks de Automação: `install()` e `uninstall()`.

---

## 📄 Licença

MIT License

---

## 👏 Créditos

Desenvolvido por **Rahpt**  

**Versão**: 1.5.1  
**Última Atualização**: 2026-02-26
