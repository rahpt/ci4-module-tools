# CodeIgniter 4 Module Development Tools

[![Version](https://img.shields.io/badge/version-1.7.0-blue.svg)](https://github.com/rahpt/ci4-module-tools)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%3E%3D8.1-brightgreen.svg)](https://php.net)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-%3E%3D4.5-orange.svg)](https://codeigniter.com)

Conjunto avançado de ferramentas de desenvolvimento, geradores de scaffolding, pipeline transacional de instalação com staging isolado, defesa profunda contra SSRF com validação por redirecionamento (`downloadWithRedirectValidation`), blindagem de arquivos ZIP contra ZIP-Slip/evasão e quarentena automática para o ecossistema CodeIgniter 4.

---

## 🏛️ Pipeline Transacional de Instalação

```text
Download Remoto (URL)
     │
     ├── 1. Validação SSRF do Host (DNS A/AAAA, Portas, Esquemas)
     ├── 2. Download via cURL com Validação Estrita a Cada Redirecionamento 3xx
     │
     ▼
Staging Isolado (writable/modules/staging/<uuid>/)
     │
     ├── 3. Verificação Criptográfica de Assinatura SHA-256
     ├── 4. ZIP Hardening:
     │      ├── Anti-ZIP-Bomb (taxa de compressão, tamanho, contagem)
     │      ├── Anti-ZIP-Slip (../, caminhos absolutos, links simbólicos)
     │      ├── Limite de Comprimento de Arquivo (maxFilenameLength <= 255)
     │      ├── Limite de Profundidade de Pastas (maxDirectoryDepth <= 10)
     │      └── Detecção de Colisão Case-Insensitive (Foo.php vs foo.php)
     ├── 5. Validação de Estrutura Obrigatória (Config/Module.php)
     ├── 6. Extração no Sandbox
     │
     ▼
Deploy Atômico e Ativação
     │
     ├── 7. Swap Atômico para app/Modules/<Nome>
     ├── 8. Registro de Namespace e Execução de Migrations
     └── 9. Hook install() e Transição de Ativação
            │
            └──► SE FALHAR: Rollback automático limpo e Quarentena com motivo
```

---

## 📋 Índice

- [Características](#-características)
- [Instalação](#-instalação)
- [Staging Atômico e Instalação Transacional](#-staging-atômico-e-instalação-transacional)
- [Camada de Segurança (SecurityValidator)](#-camada-de-segurança-securityvalidator)
  - [Hardening contra SSRF](#hardening-contra-ssrf)
  - [Blindagem de Arquivos ZIP](#blindagem-de-arquivos-zip)
- [Comandos CLI (php spark)](#-comandos-cli-php-spark)
- [Configuração (ModuleTools.php)](#-configuração-moduletoolsphp)
- [Marketplace e Painel Administrativo](#-marketplace-e-painel-administrativo)
- [Histórico de Versões](#-histórico-de-versões)
- [Licença](#-licença)

---

## ✨ Características

### Instalação & Staging Seguro
- ✅ **Staging Atômico Transacional** - Download, extração e validações executados em sandbox isolada (`WRITEPATH/modules/staging/<uuid>/`).
- ✅ **Rollback Automático e Quarentena** - Caso ocorra qualquer erro em validação de dependências, migrations ou ativação, os arquivos são revertidos e o módulo entra em quarentena sem corromper a aplicação.
- ✅ **Verificação de Hash SHA-256** - Validação de integridade do pacote baixado antes de qualquer operação em disco.
- ✅ **Auto-Resolução de Dependências** - Instalação automática e encadeada das dependências declaradas no manifesto do módulo.

### Defesa Profunda contra SSRF (Server-Side Request Forgery)
- ✅ **Validação a Cada Hop de Redirecionamento** - Interceptação de cada HTTP 3xx via cURL com re-validação completa da nova URL de destino contra regras de SSRF antes de seguir o redirect.
- ✅ **Bloqueio de Redes Locais e Privadas** - Bloqueio de loopback (`127.0.0.1`, `::1`), ranges privados IPv4 (RFC 1918, link-local RFC 3927) e IPv6 (`fc00::/7`, `fe80::/10`).
- ✅ **Prevenção de DNS Rebinding** - Resolução de todos os registros DNS (A e AAAA) com validação de todos os IPs retornados.
- ✅ **Bloqueio de Credenciais na URL** - Rejeição de URLs contendo `user:pass@host`.

### Hardening de Arquivos ZIP
- ✅ **Proteção Anti-ZIP-Slip Estrita** - Rejeição imediata de caminhos relativos maliciosos (`../`), caminhos absolutos e links simbólicos (`isLink`).
- ✅ **Limites de Estrutura** - Comprimento máximo de nomes de arquivo (`maxFilenameLength`, padrão 255) e profundidade máxima de diretórios (`maxDirectoryDepth`, padrão 10).
- ✅ **Detecção de Colisões Case-Insensitive** - Detecta conflitos como `Controller.php` e `controller.php` evitando sobrescrita não intencional em sistemas de arquivos Windows/macOS.
- ✅ **Proteção Anti-ZIP-Bomb** - Validação de proporção de compressão (`maxCompressionRatio`), tamanho descompactado e número máximo de entradas.

### Automação & Comandos Spark
- ✅ **Scaffolding Completo (`module:init`)** - Gera estrutura de Controllers, Models, Migrations, Seeders, Views e Rotas.
- ✅ **Modularização da Home (`module:modularize-home`)** - Converte a página inicial padrão do CodeIgniter 4 em um módulo nativo.
- ✅ **Ativação e Desativação Seguras** - Comandos `module:enable` e `module:disable` com transições de estado validadas.

---

## 🚀 Instalação

```bash
composer require rahpt/ci4-module-tools
```

---

## 🔒 Staging Atômico e Instalação Transacional

O utilitário `PackageInstaller` gerencia instalações com segurança transacional:

```php
use Rahpt\Ci4ModuleTools\Support\PackageInstaller;

// 1. Instalação local a partir do repositório configurado
$success = PackageInstaller::install('contratos');

// 2. Instalação remota com hash SHA-256 e validação per-redirect
$success = PackageInstaller::installFromUrl('https://packages.minhaempresa.com/contratos.zip', [
    'sha256' => '4a3b8c2d1e0f9a8b7c6d5e4f3a2b1c0d9e8f7a6b5c4d3e2f1a0b9c8d7e6f5a4b'
]);
```

---

## 🛡️ Camada de Segurança (`SecurityValidator`)

### Hardening contra SSRF

O validador atua na inspeção de URLs remotas:

```php
use Rahpt\Ci4ModuleTools\Security\SecurityValidator;

$validator = new SecurityValidator();

// 1. Valida URL contra SSRF, portas e DNS
$validator->validateUrl('https://pkg.empresa.com/modulo.zip');

// 2. Valida URL intermediária em redirecionamentos HTTP 3xx
$validator->validateRedirectUrl($urlOrigem, $urlDestino, $profundidadeAtual);
```

### Blindagem de Arquivos ZIP

Antes da descompactação de qualquer pacote:

```php
// Valida integridade estrutural, path traversal, limites de profundidade e ZIP bombs
$validator->validateZipFile('/caminho/para/staging/pacote.zip');
```

---

## ⚡ Comandos CLI (`php spark`)

### Gerenciamento & Scaffolding
```bash
# Inicializa os módulos base essenciais (Dashboard e Módulos)
php spark module:init-core

# Cria novo módulo com scaffolding completo
php spark module:init Contratos --label="Gestão de Contratos"

# Modulariza a rota inicial padrão do CodeIgniter 4
php spark module:modularize-home

# Lista módulos instalados, versões, integridade e status
php spark module:list
```

### Ciclo de Vida & Instalação
```bash
# Instala pacote local ou remoto
php spark module:install Contratos
php spark module:install https://repo.empresa.com/faturamento.zip

# Habilita ou desabilita módulos
php spark module:enable Contratos
php spark module:disable Contratos

# Publica e sincroniza assets de módulos na pasta pública (public/modules/)
php spark module:publish Contratos
php spark module:assets
```

---

## ⚙️ Configuração (`app/Config/ModuleTools.php`)

```php
<?php

namespace Config;

use Rahpt\Ci4ModuleTools\Config\ModuleTools as BaseModuleTools;

class ModuleTools extends BaseModuleTools
{
    // Repositório de pacotes locais
    public string $localRepository = APPPATH . 'Modules';

    // Permissão para instalação via download remoto
    public bool $allowRemoteInstall = true;

    // Limites de segurança para pacotes ZIP
    public int $maxZipSize            = 20971520;  // 20 MB
    public int $maxZipFiles           = 1000;      // 1.000 arquivos
    public int $maxUncompressedSize   = 104857600; // 100 MB descompactado
    public float $maxCompressionRatio = 100.0;     // Taxa de compressão máxima

    // Limites de estrutura contra evasão e ZIP-Slip
    public int $maxFilenameLength     = 255;       // Comprimento máximo de nomes de arquivos
    public int $maxDirectoryDepth     = 10;        // Profundidade máxima de diretórios

    // Parâmetros de download remoto
    public int $downloadTimeout       = 30;
    public int $maxRedirects          = 3;
    public array $allowedPorts        = [80, 443];
    public array $allowedSchemes      = ['https'];

    // Estrutura mínima obrigatória contida no ZIP
    public array $requiredStructure = [
        'Config/Module.php',
    ];
}
```

---

## 🌐 Marketplace e Painel Administrativo

O pacote provê painéis web integrados para gerenciamento:
- **Painel de Controle de Módulos**: Visualização de status em tempo real (`ativo`, `inativo`, `quarentena`), ativação com 1 clique e detalhes técnicos.
- **Gerenciador de Configurações**: Interface gráfica unificada para editar as variáveis expostas pelo método `settings()` de cada módulo.
- **Marketplace Local**: Scanner automático de diretórios com instalação instantânea de novos módulos descobertos.

---

## 🕒 Histórico de Versões

### [1.7.0] - 2026-09-26
- **Segurança**: Download cURL com validação SSRF por salto em redirecionamentos HTTP 3xx (`downloadWithRedirectValidation`).
- **Segurança**: Bloqueio de IP ranges expandido (IPv4 RFC 1918/RFC 3927/loopback e IPv6 `::1`, `fc00::/7`, `fe80::/10`).
- **Segurança**: Hardening de arquivos ZIP com limites `maxFilenameLength` e `maxDirectoryDepth`.
- **Segurança**: Detecção de colisões case-insensitive de arquivos no descompactador para mitigar sobrescrita em Windows e macOS.
- **Melhoria**: Pipeline transacional com staging em sandbox e rollback automático para quarentena em falhas.

### [1.6.0] - 2026-09-26
- **Novo**: Staging atômico isolado (`WRITEPATH/modules/staging/<uuid>/`).
- **Novo**: Verificação de hash SHA-256 e auto-resolução de dependências no `PackageInstaller`.

### [1.5.0] - 2026-02-26
- **Novo**: Desambiguação inteligente de rotas de Dashboard por nível de acesso.

### [1.0.1] - 2026-02-15
- Versão inicial estável das ferramentas de módulo.

---

## 📄 Licença

Distribuído sob a licença MIT. Veja `LICENSE` para mais detalhes.

Desenvolvido por **Rahpt**  
Mantido pela equipe Rahpt / CodeIgniter 4 Modular Platform.
