# CodeIgniter 4 Module Development Tools

[![Version](https://img.shields.io/badge/version-1.6.0-blue.svg)](https://github.com/rahpt/ci4-module-tools)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%3E%3D8.1-brightgreen.svg)](https://php.net)

Conjunto avançado de ferramentas de desenvolvimento, geradores de código, marketplace local e pipeline de instalação transacional com staging atômico e validação de segurança para o ecossistema modular CodeIgniter 4.

---

## 📋 Índice

- [Características](#-características)
- [Instalação](#-instalação)
- [Staging Atômico e Instalação Transacional](#-staging-atômico-e-instalação-transacional)
- [Camada de Segurança (SecurityValidator)](#-camada-de-segurança-securityvalidator)
- [Comandos CLI](#-comandos-cli)
- [Configuração (ModuleTools.php)](#-configuração-moduletoolsphp)
- [Marketplace e Painel Web](#-marketplace-e-painel-web)
- [Histórico de Versões](#-histórico-de-versões)
- [Licença](#-licença)

---

## ✨ Características

### Instalação & Staging Seguro
- ✅ **Staging Atômico Transacional** - Download, extração e validação de pacotes remotos em sandbox isolado (`WRITEPATH/modules/staging/<uuid>/`).
- ✅ **Rollback Automático e Quarentena** - Em caso de falha de validação, migração ou ativação, os arquivos são revertidos e o módulo entra em quarentena sem corromper a aplicação.
- ✅ **Verificação de Hash SHA-256** - Validação criptográfica do arquivo ZIP antes de qualquer operação em disco.
- ✅ **Auto-Resolução de Dependências** - Instalação automática e encadeada das dependências declaradas no manifesto do módulo.

### Segurança em Nível de Produção (SecurityValidator)
- ✅ **Prevenção contra SSRF** - Bloqueio de requisições para IPs de loopback (`127.0.0.1`, `::1`), ranges privados de rede local e portas não homologadas.
- ✅ **Proteção contra ZIP Bombs** - Validação de proporção de compressão (`maxCompressionRatio`), tamanho total descompactado e número máximo de arquivos.
- ✅ **Bloqueio de Path Traversal** - Verificação estrita contra caminhos relativos maliciosos (`../`) e bloqueio de links simbólicos dentro de arquivos ZIP.

### Automação & CLI
- ✅ **Geradores Completos (Scaffolding)** - Criação de módulos com Controllers, Models, Migrations, Seeders, Views e Rotas organizadas.
- ✅ **Modularização da Home (`module:modularize-home`)** - Converte a aplicação padrão do CodeIgniter 4 em um módulo com auto-ativação e compatibilidade com Shield.
- ✅ **Hot-Namespace Registration** - Carregamento instantâneo do namespace do módulo para execução imediata de migrações sem reiniciar o servidor.

---

## 🚀 Instalação

```bash
composer require rahpt/ci4-module-tools
```

---

## 🔒 Staging Atômico e Instalação Transacional

O instalador de pacotes (`PackageInstaller`) gerencia instalações locais ou remotas de maneira transacional:

```php
use Rahpt\Ci4ModuleTools\Support\PackageInstaller;

// 1. Instalação de pacote local (do repositório local)
$success = PackageInstaller::install('financeiro');

// 2. Instalação remota com hash SHA-256 e staging isolado
$success = PackageInstaller::installFromUrl('https://repositorio.exemplo.com/modulos/faturamento.zip', [
    'sha256' => '4a3b8c2d1e0f9a8b7c6d5e4f3a2b1c0d9e8f7a6b5c4d3e2f1a0b9c8d7e6f5a4b'
]);
```

### Pipeline de Instalação:
1. **Validação SSRF**: O `SecurityValidator` valida se a URL é externa, pública e segura.
2. **Download no Sandbox**: O pacote é salvo em `writable/modules/staging/{uuid}/module.zip`.
3. **Checagem de Hash**: O SHA-256 é verificado contra a assinatura esperada.
4. **Varredura do ZIP**: Bloqueio de ZIP bombs, links simbólicos e path traversal.
5. **Extração Segura**: Os arquivos são descompactados dentro da sandbox.
6. **Deploy Atômico**: Cópia recursiva para `app/Modules/{Modulo}`.
7. **Migrations e Hooks**: Execução imediata de migrações e do hook `install()`.
8. **Ativação e Quarentena**: Se qualquer etapa falhar, os arquivos implantados são destruídos e o registro é enviado para quarentena com o motivo da falha.

---

## 🛡️ Camada de Segurança (`SecurityValidator`)

O validador atua como guardião na ingestão de pacotes:

```php
use Rahpt\Ci4ModuleTools\Security\SecurityValidator;

$validator = new SecurityValidator();

// 1. Valida URL contra SSRF
$validator->validateUrl('https://pkg.empresa.com/modulo.zip');

// 2. Valida arquivo ZIP contra ataques estruturais
$validator->validateZipFile('/caminho/para/arquivo.zip');
```

---

## ⚡ Comandos CLI

O pacote adiciona comandos ao utilitário `php spark`:

### Gerenciamento de Módulos
```bash
# Inicializa os módulos essenciais do sistema (Dashboard e Gerenciador de Módulos)
php spark module:init-core

# Cria um novo módulo com scaffolding completo (CRUD, Model, Migration e View)
php spark module:init Contratos --label="Gestão de Contratos"

# Converte o Home padrão do CI4 em módulo com auto-ativação
php spark module:modularize-home

# Lista todos os módulos, status e integridade
php spark module:list
```

### Ciclo de Vida e Instalação
```bash
# Instala módulo localmente ou via URL remota
php spark module:install Contratos

# Ativa ou desativa um módulo com segurança transacional
php spark module:enable Contratos
php spark module:disable Contratos

# Publica e sincroniza assets de módulos para a pasta pública
php spark module:publish Contratos
php spark module:assets
```

---

## ⚙️ Configuração (`app/Config/ModuleTools.php`)

Personalize os limites de segurança e parâmetros de repositório:

```php
<?php

namespace Config;

use Rahpt\Ci4ModuleTools\Config\ModuleTools as BaseModuleTools;

class ModuleTools extends BaseModuleTools
{
    // Repositório local de módulos pré-baixados
    public string $localRepository = APPPATH . 'Modules';

    // Permite instalação remota via URL
    public bool $allowRemoteInstall = true;

    // Limites de segurança para pacotes ZIP
    public int $maxZipSize           = 20971520;  // 20 MB
    public int $maxZipFiles          = 1000;      // Máximo de 1.000 arquivos
    public int $maxUncompressedSize  = 104857600; // Máximo de 100 MB descompactado
    public float $maxCompressionRatio = 100.0;   // Proteção anti ZIP-bomb

    // Tempo limite de download remoto
    public int $downloadTimeout = 30;

    // Portas permitidas para download
    public array $allowedPorts = [80, 443];

    // Modo debug para auditoria em desenvolvimento
    public bool $debugMode = true;
}
```

---

## 🌐 Marketplace e Painel Web

O pacote inclui interfaces completas para administração:
- **Painel de Módulos**: Visualização de status (`ativo`, `inativo`, `quarentena`), ativação com 1 clique e detalhes técnicos.
- **Gerenciador de Configurações**: Interface gráfica unificada para editar as variáveis expostas pelo método `settings()` de cada módulo.
- **Marketplace Scanner**: Varre repositórios locais e apresenta módulos disponíveis para instalação instantânea.

---

## 🕒 Histórico de Versões

### [1.6.0] - 2026-09-26
- **Novo**: Pipeline de instalação com Staging Atômico (`WRITEPATH/modules/staging/<uuid>/`).
- **Novo**: Rollback automático transacional e quarentena em falhas de instalação.
- **Novo**: Verificação de integridade via checksum SHA-256 no `PackageInstaller`.
- **Novo**: `SecurityValidator` robusto com proteção contra SSRF, ZIP Bombs e Path Traversal.
- **Novo**: Resolução e auto-instalação recursiva de dependências de módulos.
- **Novo**: Configuração de segurança avançada em `ModuleTools.php`.

### [1.5.1] - 2026-02-26
- **Fix**: Redirecionamento para novos usuários sem UID gerado.
- **Melhoria**: Geração automática de UID no primeiro acesso ao Dashboard.

### [1.5.0] - 2026-02-26
- **Novo**: Desambiguação inteligente de rotas de Dashboard por nível de acesso.

### [1.4.0] - 2026-02-22
- **Novo**: Auto-ativação do módulo Home no comando `module:modularize-home`.
- **Melhoria**: Injeção de views baseada em regex preservando o layout base.

### [1.3.0] - 2026-02-22
- **Novo**: Comandos `module:modularize-home` e `module:init-core`.
- **Melhoria**: Integração profunda com CodeIgniter Shield.

### [1.2.0] - 2026-02-18
- **Novo**: Gerenciador centralizado de configurações (`SettingsManager`).
- **Melhoria**: Suporte a Hooks de Automação: `install()` e `uninstall()`.

---

## 📄 Licença

MIT License. Desenvolvido por **Rahpt**.
