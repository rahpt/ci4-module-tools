# CodeIgniter 4 Module Development Tools

[![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)](https://github.com/rahpt/ci4-module-tools)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%3E%3D8.1-brightgreen.svg)](https://php.net)

Ferramentas de desenvolvimento para o sistema modular CodeIgniter 4. Inclui marketplace de módulos, instalação/desinstalação, e validações de segurança.

---

## 📋 Características

### Core Features
- ✅ **Module Marketplace** - Interface web para gerenciar módulos
- ✅ **Local Repository** - Instalar módulos do repositório local com 1 clique
- ✅ **Remote Installation** - Baixar e instalar de URLs (com validações)
- ✅ **Security Validations** - Proteção contra SSRF, Zip Slip, DoS
- ✅ **Module Generator** - Cria estrutura de módulos automaticamente

### Security Features
- ✅ **SSRF Prevention** - Bloqueia acesso a IPs privados
- ✅ **Zip Slip Protection** - Valida estrutura de arquivos ZIP
- ✅ **Size Limits** - Limita tamanho de downloads
- ✅ **Timeout Control** - Previne travamentos
- ✅ **Dependency Validation** - Verifica dependências antes de ativar

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
    // Path do repositório local (pode ser absoluto ou relativo)
    public string $localRepository = 'c:/www/mods/Modules';
    
    // Tamanho máximo de ZIP (bytes)
    public int $maxZipSize = 52428800; // 50MB
    
    // Timeout de download (segundos)
    public int $downloadTimeout = 30;
    
    // Modo debug (logs extras)
    public bool $debugMode = true; // false em produção!
    
    // Permitir instalação remota
    public bool $allowRemoteInstall = true; // false em produção!
    
    // Esquemas permitidos
    public array $allowedSchemes = ['https']; // Apenas HTTPS
}
```

---

## 📖 Uso Básico

### Marketplace Interface

Acesse: `/system/modules/marketplace`

**Funcionalidades**:
- 📦 **Ver módulos disponíveis** no repositório local
- ⬇️ **Instalar com 1 clique**
- 🗑️ **Desinstalar módulos** inativos
- ✅ **Ver status** (instalado/ativo)
- 💾 **Instalar de URL** (se habilitado)

### Instalação Local

1. Coloque o módulo em: `c:/www/mods/Modules/NomeDoModulo/`
2. Acesse o marketplace
3. Clique em "Instalar"
4. ✅ Módulo é copiado para `app/Modules/`
5. ✅ Estrutura é validada
6. ✅ Pronto para ativar!

### Instalação Remota (Opcional)

```php
// POST /system/modules/install
{
    "url": "https://example.com/module.zip"
}
```

**Validações Automáticas**:
- ✅ Verifica se é HTTPS
- ✅ Bloqueia IPs privados
- ✅ Limita tamanho
- ✅ Valida estrutura do ZIP
- ✅ Verifica path traversal

---

## 🔒 Segurança

### 1. SSRF Prevention

**Problema**: URLs maliciosas podem atacar servidores internos

**Solução**: Blacklist de IPs privados

```php
// ❌ Bloqueado
https://127.0.0.1/module.zip
https://192.168.1.1/module.zip
https://10.0.0.1/module.zip

// ✅ Permitido
https://trusted-source.com/module.zip
```

### 2. Zip Slip Protection

**Problema**: Arquivos ZIP maliciosos com path traversal

**Solução**: Validação de caminhos

```php
// ❌ Bloqueado
../../../etc/passwd
/var/www/sensitive.php

// ✅ Permitido
Config/Module.php
Controllers/MyController.php
```

### 3. Size & Timeout Limits

**Problema**: Downloads grandes podem causar DoS

**Solução**: Limites configuráveis

```php
// Máximo 50MB
public int $maxZipSize = 52428800;

// Timeout de 30s
public int $downloadTimeout = 30;
```

---

## 🎯 Module Generator

### Gerar Módulo via Interface

```
POST /system/modules/generate
{
    "name": "Products",
    "namespace": "App\\Modules\\Products"
}
```

### Estrutura Gerada

```
app/Modules/Products/
├── Config/
│   └── Module.php
├── Controllers/
│   └── ProductsController.php
├── Models/
│   └── ProductModel.php
├── Views/
│   └── index.php
└── README.md
```

**Config/Module.php**:
```php
<?php

namespace App\Modules\Products\Config;

use Rahpt\Ci4Module\BaseModule;

class Module extends BaseModule
{
    public string $name = 'Products';
    public string $label = 'Gerenciamento de Produtos';
    public string $slug = 'products';
    public string $version = '1.0.0';
    public string $theme = 'adminlte';
    public array $require = [];
    
    public function menu(): array
    {
        return [
            [
                'label' => 'Produtos',
                'url' => 'products',
                'icon' => 'fas fa-box'
            ]
        ];
    }
}
```

---

## 🔧 API do ModuleController

### GET /system/modules

Lista todos os módulos instalados.

**Response**:
```json
{
    "modules": [
        {
            "name": "Dashboard",
            "slug": "dashboard",
            "version": "1.0.0",
            "active": true,
            "installed_at": "2026-02-15 10:00:00"
        }
    ]
}
```

### POST /system/modules/install

Instala um módulo.

**Request**:
```json
{
    "local": "Dashboard"
    // ou
    "url": "https://example.com/module.zip"
}
```

**Response**:
```json
{
    "success": true,
    "message": "Módulo Dashboard instalado com sucesso!"
}
```

### POST /system/modules/uninstall

Desinstala um módulo inativo.

**Request**:
```json
{
    "module": "Dashboard"
}
```

**Response**:
```json
{
    "success": true,
    "message": "Módulo Dashboard desinstalado com sucesso!"
}
```

---

## 📊 Security Validator

### Validar URL

```php
use Rahpt\Ci4ModuleTools\Security\SecurityValidator;

$validator = new SecurityValidator();

try {
    $validator->validateUrl('https://example.com/module.zip');
    echo "URL segura!";
} catch (Exception $e) {
    echo "URL bloqueada: " . $e->getMessage();
}
```

**Validações**:
- ✅ Formato válido
- ✅ Esquema permitido (HTTPS)
- ✅ Não é IP privado
- ✅ Extensão .zip

### Validar ZIP

```php
$validator->validateZipFile('/path/to/module.zip');
```

**Validações**:
- ✅ Arquivo existe
- ✅ Tamanho dentro do limite
- ✅ ZIP válido
- ✅ Sem path traversal
- ✅ Estrutura de módulo válida

---

## ⚙️ Configuração de Produção

### Recomendações

```php
// app/Config/ModuleTools.php
class ModuleTools extends BaseModuleTools
{
    // ⚠️ IMPORTANTE: Desabilitar em produção
    public bool $debugMode = false;
    public bool $allowRemoteInstall = false;
    
    // Mais restritivo
    public int $maxZipSize = 10485760; // 10MB
    public int $downloadTimeout = 15;   // 15s
    
    // Apenas HTTPS
    public array $allowedSchemes = ['https'];
}
```

### Logs

Todas as operações são registradas:

```
[2026-02-15 14:30:00] INFO: Attempting local module installation: Dashboard
[2026-02-15 14:30:01] INFO: Module Dashboard installed successfully
[2026-02-15 14:30:05] INFO: Module 'dashboard' activated
[2026-02-15 14:35:10] WARNING: Cannot activate 'products': Missing dependency: auth
```

---

## 🧪 Testes

```bash
composer test
```

---

## 🔧 Troubleshooting

### "Instalação remota desabilitada"

**Solução**: `public bool $allowRemoteInstall = true;`

### "URL scheme not allowed"

**Solução**: Usar HTTPS ou adicionar 'http' em `allowedSchemes` (não recomendado)

### "Access to private networks not allowed"

**Solução**: Isso é uma proteção. Use instalação local para desenvolvimento.

### "Module structure validation failed"

**Solução**: Verificar se módulo tem `Config/Module.php`

---

## 📚 Documentação Relacionada

- [ci4-module](../ci4-module/README.md) - Core system
- [ci4-module-nav](../ci4-module-nav/README.md) - Navigation
- [ci4-module-theme](../ci4-module-theme/README.md) - Themes

---

## 📄 Licença

MIT License

---

## 👏 Créditos

Desenvolvido por **Rahpt**  

---

**Versão**: 2.0.0  
**Última Atualização**: 2026-02-15
