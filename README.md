# CodeIgniter 4 Module Development Tools

[![Version](https://img.shields.io/badge/version-1.2.0-blue.svg)](https://github.com/rahpt/ci4-module-tools)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%3E%3D8.1-brightgreen.svg)](https://php.net)

Ferramentas de desenvolvimento para o sistema modular CodeIgniter 4. Inclui marketplace de módulos, gerenciador de configurações, e automação de ciclo de vida.

---

## 📋 Características

### Core Features
- ✅ **Module Marketplace** - Interface web para gerenciar módulos
- ✅ **Settings Manager** - Gerenciador centralizado de configurações para todos os módulos
- ✅ **Lifecycle Automation** - Execução automática de `install()` e `uninstall()`
- ✅ **Local Repository** - Instalar módulos do repositório local com 1 clique
- ✅ **Remote Installation** - Baixar e instalar de URLs com segurança
- ✅ **Hot-namespace Registration** - Registro imediato de namespaces para rodar migrações sem restart

---

## 🚀 Novidades na v1.2.0

### Centralização de Configurações
O novo sistema de **Settings** permite que cada módulo defina suas próprias configurações no arquivo `Config/Module.php`. O `ModuleController` agora possui uma aba centralizada que busca automaticamente essas definições e permite ao admin editar os valores via UI, salvando-os via `codeigniter4/settings`.

### Automação de Migrações
Ao instalar um módulo, o sistema agora registra o namespace no Autoloader instantaneamente e dispara o método `install()`. Se o módulo tiver migrações, elas serão executadas imediatamente.

### Desinstalação Limpa
Ao clicar em excluir no marketplace, o sistema executa o hook `uninstall()` do módulo, permitindo que ele faça o rollback de suas tabelas antes da remoção dos arquivos físicos.

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
    // Path do repositório local
    public string $localRepository = 'c:/www/mods/Modules';
    
    // Modo debug (logs extras)
    public bool $debugMode = true;
}
```

---

## 📖 Histórico de Versões

### [1.2.0] - 2026-02-18
- **Novo**: Implementado **Settings Manager** para gerenciamento centralizado de configurações de módulos.
- **Melhoria**: Novo fluxo de instalação com **Hot-namespace Registration** para execução imediata de migrações.
- **Novo**: Suporte a Hooks de Automação: `install()` dispara `latest()` e `uninstall()` disparando rollback.
- **Melhoria**: `PackageInstaller` aprimorado com carregamento explícito de classes durante a instalação.
- **UX**: Interface do Gerenciador de Módulos atualizada com suporte a configurações globais.

### [1.1.0] - 2026-02-16
- **Segurança (CSRF)**: Migração de todas as ações sensíveis do `ModuleController` para `POST`.
- **Segurança (SSRF)**: Melhoria no `SecurityValidator` com blacklist expandida.
- **CLI**: Adicionado comando `module:import-manager`.

### [1.0.1] - 2026-02-15
- Versão inicial com Marketplace Local e Gerador de Módulos.

---

## 📄 Licença

MIT License

---

## 👏 Créditos

Desenvolvido por **Rahpt**  

**Versão**: 1.2.0  
**Última Atualização**: 2026-02-18
