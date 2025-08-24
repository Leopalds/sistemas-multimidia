# Sistema de Reconhecimento Facial - Parte 2

## 📋 Visão Geral

Este é um sistema completo de reconhecimento facial desenvolvido em Laravel (PHP) com processamento Python para análise de imagens e vídeos. O sistema permite fazer upload de mídias, identificar pessoas através de reconhecimento facial e gerenciar um banco de dados de rostos conhecidos.

## 🚀 Funcionalidades Principais

### 1. **Upload de Mídias**
- **Interface Drag & Drop**: Sistema moderno de upload com arrastar e soltar
- **Suporte a Múltiplos Formatos**: Imagens (JPG, PNG, etc.) e Vídeos (MP4, AVI, etc.)
- **Barra de Progresso**: Acompanhamento visual do upload em tempo real
- **Validação de Arquivos**: Verificação automática de tipos e tamanhos

### 2. **Reconhecimento Facial**
- **Processamento Automático**: Análise automática de rostos em imagens e vídeos
- **Algoritmo Avançado**: Utiliza bibliotecas Python (face_recognition, OpenCV) para detecção precisa
- **Métricas de Confiança**: Porcentagem de confiança para cada identificação
- **Processamento em Lote**: Suporte a múltiplas mídias simultaneamente

### 3. **Gerenciamento de Pessoas**
- **Banco de Rostos**: Armazenamento de características faciais para identificação
- **Edição de Nomes**: Interface para associar nomes aos rostos identificados
- **Histórico de Detecções**: Rastreamento de todas as aparições de cada pessoa
- **Busca e Filtros**: Sistema de pesquisa e paginação para grandes volumes

### 4. **Visualização de Mídias**
- **Player de Vídeo**: Reprodução com timeline sincronizado com detecções
- **Overlay de Detecções**: Caixas delimitadoras sobre rostos identificados
- **Informações em Tempo Real**: Dados de confiança e identificação
- **Navegação por Frames**: Para vídeos, navegação frame a frame

## 🛠️ Tecnologias Utilizadas

### Backend
- **Laravel 10**: Framework PHP para API e gerenciamento
- **SQLite**: Banco de dados para armazenamento local
- **Redis**: Sistema de filas para processamento assíncrono
- **Queue Jobs**: Processamento em background

### Frontend
- **Vue.js 3**: Interface reativa e moderna
- **Inertia.js**: Integração SPA com Laravel
- **Tailwind CSS**: Estilização responsiva e moderna
- **FontAwesome**: Ícones para interface

### Processamento de IA
- **Python 3.8+**: Scripts de reconhecimento facial
- **face_recognition**: Biblioteca principal para detecção
- **OpenCV**: Processamento de vídeo e imagens
- **NumPy**: Computação numérica

## 📁 Estrutura do Projeto

```
Parte 2/
├── app/                    # Aplicação Laravel
│   ├── Http/Controllers/  # Controladores da API
│   ├── Jobs/              # Jobs de processamento
│   ├── Models/            # Modelos de dados
│   └── Policies/          # Políticas de acesso
├── Python/                # Scripts Python
│   ├── facesvc/           # Serviço de reconhecimento
│   ├── main.py            # Script principal
│   ├── worker.py          # Worker para filas
│   └── requirements.txt   # Dependências Python
├── resources/js/          # Frontend Vue.js
│   ├── Pages/             # Páginas da aplicação
│   └── Components/        # Componentes reutilizáveis
└── database/              # Migrações e seeders
```

## 🚀 Como Usar

### 1. **Configuração Inicial**

```bash
# Instalar dependências PHP
composer install

# Instalar dependências Node.js
npm install

# Configurar banco de dados
php artisan migrate

# Gerar chave da aplicação
php artisan key:generate
```

### 2. **Configuração Python**

```bash
cd Python

# Criar ambiente virtual
python -m venv venv

# Ativar ambiente (Linux/Mac)
source venv/bin/activate

# Ativar ambiente (Windows)
venv\Scripts\activate

# Instalar dependências
pip install -r requirements.txt
```

### 3. **Configuração do Redis**

```bash
# Instalar Redis (Ubuntu/Debian)
sudo apt-get install redis-server

# Iniciar serviço
sudo systemctl start redis-server

# Verificar status
redis-cli ping
```

### 4. **Executar o Sistema**

```bash
# Terminal 1: Servidor Laravel
php artisan serve

# Terminal 2: Worker Python
cd Python
python worker.py

# Terminal 3: Compilar assets (desenvolvimento)
npm run dev
```

## 📱 Interface do Usuário

### **Página de Upload**
- Arraste arquivos para a área demarcada
- Visualize previews antes do envio
- Acompanhe o progresso do upload
- Receba feedback de sucesso/erro

### **Lista de Mídias**
- Grid responsivo com thumbnails
- Status de processamento (pendente/processado/falhou)
- Ações rápidas (visualizar/excluir)
- Filtros por tipo e status

### **Visualização de Mídia**
- **Imagens**: Overlay com caixas delimitadoras dos rostos
- **Vídeos**: Player com timeline sincronizado
- **Detecções**: Lista de pessoas identificadas por frame
- **Edição**: Modificar nomes das pessoas identificadas

### **Gerenciamento de Pessoas**
- Lista paginada de todas as pessoas
- Estatísticas de detecções
- Ações de edição e exclusão
- Busca e filtros

## 🔧 Configurações Avançadas

### **Variáveis de Ambiente Python**

Crie um arquivo `Python/.env`:

```env
# Configurações do Redis
REDIS_URL=redis://127.0.0.1:6379/0
LARAVEL_QUEUE_KEY=queues:face

# Configurações da API Laravel
LARAVEL_API_BASE=http://localhost:8000/api

# Configurações de Reconhecimento
FACE_THRESHOLD=0.6
FACE_MODEL=hog
FACE_UPSAMPLE=1
FRAME_SKIP=5

# Caminho do banco SQLite
SQLITE_PATH=database/database.sqlite
```

### **Configurações do Laravel**

```php
// config/queue.php
'default' => env('QUEUE_CONNECTION', 'redis'),

'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'queues:face'),
        'retry_after' => 90,
        'block_for' => null,
    ],
],
```

## 📊 Processamento de Mídias

### **Fluxo de Processamento**

1. **Upload**: Usuário envia arquivo via interface
2. **Enfileiramento**: Job é adicionado à fila Redis
3. **Processamento**: Worker Python processa a mídia
4. **Reconhecimento**: Análise facial com algoritmos de IA
5. **Resultado**: Dados salvos no banco e interface atualizada

### **Tipos de Mídia Suportados**

- **Imagens**: JPG, PNG, GIF, BMP
- **Vídeos**: MP4, AVI, MOV, WMV
- **Tamanho**: Até 100MB por arquivo
- **Resolução**: Suporta HD e 4K

## 🎯 Casos de Uso

### **Segurança e Monitoramento**
- Identificação de pessoas em câmeras de segurança
- Controle de acesso baseado em reconhecimento facial
- Monitoramento de presença em eventos

### **Organização de Fotos**
- Categorização automática de álbuns familiares
- Identificação de pessoas em eventos
- Organização de fotos profissionais

### **Análise de Vídeos**
- Rastreamento de pessoas em filmagens
- Análise de comportamento em vídeos
- Estatísticas de aparições

## 🚨 Solução de Problemas

### **Erros Comuns**

1. **Redis não conecta**
   ```bash
   sudo systemctl status redis-server
   redis-cli ping
   ```

2. **Dependências Python faltando**
   ```bash
   pip install -r requirements.txt
   ```

3. **Permissões de arquivo**
   ```bash
   chmod -R 755 storage/
   chmod -R 755 bootstrap/cache/
   ```

### **Logs e Debug**

```bash
# Logs do Laravel
tail -f storage/logs/laravel.log

# Logs do Python
# Os logs aparecem no terminal do worker
```

## 📝 Licença

Este projeto é parte de um trabalho acadêmico sobre Sistemas Multimídia.
