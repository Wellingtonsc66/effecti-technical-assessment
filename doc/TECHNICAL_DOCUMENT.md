# Documento Técnico

## Visão geral

Esta aplicação foi construída como uma solução de gestão de clientes, serviços e contratos, com backend em Laravel e interface administrativa em Vue 3.

O objetivo foi entregar uma base simples de operar, mas com separação razoável de responsabilidades, regras de negócio explícitas e espaço para evolução.

## Estrutura da aplicação

O projeto está organizado em uma estrutura tradicional de Laravel, com separação entre domínio HTTP, regras de validação, serviços de negócio, persistência e interface web.

### Backend

- `app/Http/Controllers`
Responsável por receber as requisições, delegar a execução e devolver a resposta.

- `app/Http/Requests`
Centraliza validações de entrada para clientes, contratos e itens de contrato.

- `app/Http/Resources`
Centraliza a serialização das respostas da API. Atualmente o retorno de contratos está encapsulado em `ContractResource`.

- `app/Models`
Representa as entidades principais do sistema, como `Client`, `Service`, `Contract`, `ContractItem` e `ContractChange`.

- `app/Services`
Contém a lógica de negócio que não deve ficar acoplada ao controller.

- `app/Services/Pricing`
Agrupa a lógica de cálculo financeiro do contrato e as regras de ajuste aplicadas ao total.

- `app/Rules`
Contém regras customizadas de validação, como CPF/CNPJ.

- `app/Enums`
Padroniza estados de cliente e contrato.

- `database/migrations`
Define a estrutura do banco de dados.

- `tests/Feature`
Concentra os testes de comportamento dos fluxos principais da API.

### Frontend

- `resources/js/App.vue`
Tela principal da aplicação, com os fluxos de clientes, serviços e contratos.

- `resources/js/app.js`
Bootstrap do Vue e configuração do PrimeVue.

- `resources/css/app.css`
Entrada de CSS com Tailwind CSS 4.

### Infraestrutura

- `docker-compose.yml`
Orquestra os containers da aplicação.

O MySQL persiste os dados em `./.docker/mysql/dbdata`, montado em `/var/lib/mysql`, para manter o banco disponível após recriação dos containers. Esse diretório local é ignorado pelo Git.

- `docker/php/Dockerfile`
Define o ambiente PHP-FPM 8.5 usado pelo projeto.

## Decisões técnicas tomadas

### 1. Laravel + Vue no mesmo repositório

Foi escolhida uma estrutura monolítica, com backend e frontend no mesmo projeto, porque isso reduz complexidade operacional para a avaliação e facilita subir tudo com Docker.

### 2. API REST separada da interface

A interface Vue consome endpoints em `/api`, mantendo uma separação clara entre camada visual e camada de dados.

Essa escolha facilita:

- testar o backend de forma independente
- evoluir a interface sem misturar regra de negócio no Blade
- reaproveitar a API em outro cliente, se necessário

### 3. Regras de negócio fora dos controllers

Os controllers foram mantidos como camada de orquestração.

As decisões principais ficaram em serviços dedicados, especialmente:

- `ContractManagementService`
- `ContractPricingService`
- `QuantityDiscountPricingRule`

Essa abordagem reduz duplicação, melhora testabilidade e evita controllers inchados.

### 4. Serialização com Resource

O retorno de contrato foi extraído para `ContractResource`, em vez de manter a montagem manual do payload dentro do controller.

Isso melhora:

- legibilidade do controller
- consistência do formato de resposta
- manutenção futura do contrato de API

### 5. Validação de entrada com Form Requests e Rule customizada

As validações HTTP foram colocadas em Form Requests para manter o controller limpo e garantir que a entrada chegue já validada.

Para CPF/CNPJ, foi criada uma regra customizada específica, em vez de uma validação superficial de string.

### 6. Helper global para normalização de números

Foi criado `helper.php` com a função `onlyNumbers`, registrado no Composer, para centralizar a remoção de caracteres não numéricos em pontos do backend.

Essa decisão evita repetição de `preg_replace` espalhado pelo código.

### 7. Docker como fluxo oficial de execução

O projeto foi documentado e preparado com Docker como caminho oficial de execução.

Isso reduz divergência entre ambientes e deixa a avaliação mais previsível.

## Organização das camadas

### Camada de entrada HTTP

Os endpoints são definidos em `routes/api.php` e apontam para controllers REST.

Os controllers:

- recebem a requisição
- acionam validação via Request
- delegam a execução para serviços ou modelos
- devolvem `JsonResponse` ou `Resource`

### Camada de validação

As regras de entrada ficam em classes específicas:

- `ClientRequest`
- `ServiceRequest`
- `ContractRequest`
- `ContractItemRequest`
- `CpfCnpj`

Essa camada garante que erros de entrada sejam tratados antes de chegar ao núcleo da regra de negócio.

### Camada de negócio

O núcleo do comportamento do sistema está em serviços.

#### `ContractManagementService`

Responsável por:

- criar contratos
- atualizar contratos
- adicionar itens
- remover itens
- bloquear edição de contratos cancelados
- registrar histórico de alterações

#### `ContractPricingService`

Responsável por:

- calcular subtotal
- aplicar ajustes de pricing
- devolver total final formatado

#### `QuantityDiscountPricingRule`

Implementa uma regra concreta de desconto baseada em quantidade.

O desenho com interface + regra concreta permite incluir novas regras sem alterar a estrutura principal do cálculo.

### Camada de apresentação da API

`ContractResource` concentra a serialização do contrato com:

- dados básicos
- cliente relacionado
- itens com total por linha
- histórico de mudanças
- pricing consolidado

### Camada de persistência

Os Models Eloquent representam as entidades e seus relacionamentos.

O acesso ao banco está concentrado em Models e nos serviços, que usam transações onde a operação precisa de consistência.

## Implementação das regras de negócio

### 1. Cliente deve ter CPF ou CNPJ válido

O documento informado é normalizado para conter apenas números e validado por uma regra customizada.

Essa validação impede:

- documentos inválidos
- documentos repetidos

### 2. E-mail do cliente deve ser válido

O campo usa validação RFC e também unicidade no banco.

### 3. Contratos cancelados não podem ser editados

Quando o contrato está com status `canceled`, o sistema bloqueia:

- atualização do contrato
- adição de itens
- remoção de itens

Esse bloqueio acontece no serviço de negócio, não apenas na interface, o que protege a regra mesmo em chamadas diretas à API.

### 4. Itens de contrato usam valor do serviço por padrão

Ao adicionar um item ao contrato, caso o valor unitário não seja enviado, o sistema utiliza automaticamente o `monthly_base_value` do serviço.

### 5. Histórico de alterações do contrato

Toda operação relevante em contrato gera um registro em `ContractChange`.

Hoje isso inclui:

- criação do contrato
- atualização do contrato
- inclusão de item
- remoção de item

### 6. Regra de negócio aberta: desconto por quantidade

Para atender ao item aberto da avaliação, foi implementada uma regra de precificação extensível.

Regra aplicada:

- se um item possui quantidade maior ou igual a 5
- o sistema aplica 10% de desconto sobre o total daquele item

Esse desconto aparece em `pricing.adjustments` e compõe o total final mensal do contrato.

### 7. Cálculo financeiro em centavos

O cálculo do pricing foi estruturado com conversão para centavos para reduzir problemas comuns de precisão com valores decimais em operações monetárias.

## O que melhoraria com mais tempo

### 1. Expandir o uso de Resources

Hoje o padrão foi aplicado ao contrato. Com mais tempo, eu padronizaria também clientes e serviços com `ClientResource` e `ServiceResource`.

### 2. Quebrar o frontend em componentes menores

Atualmente a interface está concentrada em `App.vue` para simplificar a entrega.

Com mais tempo, eu dividiria em componentes como:

- formulário de cliente
- tabela de serviços
- formulário de contrato
- card de contrato
- painel de pricing

Isso aumentaria legibilidade, reuso e manutenção.

### 3. Aumentar cobertura de testes

Eu incluiria mais cenários de:

- atualização e exclusão
- histórico de alterações
- limites da regra de desconto
- paginação
- testes unitários do cálculo de pricing

### 4. Melhorar a modelagem de valores monetários

Hoje a solução já converte para centavos durante o cálculo, mas poderia evoluir para um value object de dinheiro ou uma abstração mais forte para operações monetárias.

### 5. Adicionar documentação formal da API

Seria útil incluir OpenAPI/Swagger ou uma coleção pronta para Postman/Insomnia, para facilitar validação externa da API.

### 6. Refinar autorização e segurança

Como a avaliação está focada em fluxo funcional, não foi criada uma camada completa de autenticação/autorização.

Com mais tempo, eu adicionaria:

- autenticação
- policies
- escopo por usuário ou perfil

### 7. Evoluir a estratégia de pricing

A estrutura atual já permite novas regras, mas eu consideraria:

- regras progressivas por faixa
- vigência promocional
- multa por cancelamento
- reajuste por período contratual

### 8. Melhorar UX operacional

Alguns pontos naturais de evolução seriam:

- filtros e busca nas listagens
- paginação visual no frontend
- confirmação de exclusão com componente dedicado
- feedback mais detalhado por campo

## Conclusão

A solução foi construída priorizando clareza de estrutura, separação de responsabilidades e implementação explícita das regras centrais do domínio.

Mesmo sendo uma entrega de avaliação, a base já está organizada para evolução incremental, principalmente no eixo de serialização, expansão de regras de pricing, testes e decomposição do frontend.
