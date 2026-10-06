# Tags HTML usadas no HighTech

As páginas do projeto são escritas em PHP e geram HTML para o navegador. Portanto, as tags abaixo aparecem em arquivos `.php`, e não em arquivos `.html` separados. Esta lista descreve as tags encontradas nas telas do projeto e o papel que desempenham nelas.

## Estrutura do documento

| Tag | Uso no projeto |
| --- | --- |
| `<html>` | Elemento raiz que envolve o documento HTML completo. |
| `<head>` | Agrupa metadados e recursos da página, como título, configuração de tela e folha de estilos. |
| `<meta>` | Informa metadados ao navegador; por exemplo, codificação de caracteres e configuração responsiva da página. |
| `<title>` | Define o título exibido na aba ou janela do navegador. |
| `<link>` | Carrega a folha de estilos compartilhada `assets/style.css`. |
| `<style>` | Contém regras CSS específicas da página, usado, por exemplo, na apresentação do certificado. |
| `<body>` | Envolve o conteúdo visível da página. |

## Regiões e organização do conteúdo

| Tag | Uso no projeto |
| --- | --- |
| `<header>` | Apresenta o cabeçalho comum do site, com logo, nome e identificação da HighTech. |
| `<nav>` | Agrupa os links de navegação principal, incluindo opções que variam conforme o perfil conectado. |
| `<main>` | Marca o conteúdo principal da página, separado da navegação e do rodapé. |
| `<section>` | Agrupa conteúdo relacionado, como áreas de cursos, formulários ou informações do portal. |
| `<article>` | Representa um item que pode ser entendido separadamente, como um cartão de curso, vaga, talento ou certificado. |
| `<footer>` | Exibe o rodapé compartilhado com contato, créditos e direitos autorais. |
| `<div>` | Agrupa elementos para estruturar o layout ou aplicar classes e estilos; não acrescenta significado semântico por si só. |

## Títulos e texto

| Tag | Uso no projeto |
| --- | --- |
| `<h1>` | Título principal da página ou identificação principal do sistema. |
| `<h2>` | Título de uma seção importante ou do rodapé. |
| `<h3>` | Título de subseções, cartões ou blocos de conteúdo. |
| `<h4>` | Título de nível inferior em áreas com subdivisões. |
| `<p>` | Forma parágrafos de descrição, instruções, boas-vindas e informações. |
| `<strong>` | Dá ênfase semântica a textos importantes, como rótulos e avisos. |
| `<small>` | Exibe observações auxiliares, como requisitos, categorias ou datas. |
| `<span>` | Agrupa texto curto em linha, por exemplo, informações do usuário e indicadores de estado. |
| `<br>` | Insere uma quebra de linha em locais pontuais, como em formulários ou células de tabela. |
| `<hr>` | Separa visualmente blocos de conteúdo, como áreas em telas de login e cadastro. |

## Links, listas e imagens

| Tag | Uso no projeto |
| --- | --- |
| `<a>` | Cria links para navegar entre páginas, abrir conteúdos ou iniciar ações de navegação. |
| `<img>` | Exibe uma imagem; no cabeçalho, é usada para mostrar o logo da HighTech. |
| `<ul>` | Cria uma lista sem ordem numérica para conjuntos de itens. |
| `<ol>` | Cria uma lista numerada quando a sequência dos itens importa. |
| `<li>` | Representa cada item dentro de uma lista `<ul>` ou `<ol>`. |

## Formulários

| Tag | Uso no projeto |
| --- | --- |
| `<form>` | Agrupa campos e botões e envia os dados para processamento pelo PHP. |
| `<label>` | Identifica um campo do formulário e ajuda a associar o texto ao controle correspondente. |
| `<input>` | Recebe valores simples, como texto, e-mail, senha, números, datas ou opções de seleção. |
| `<select>` | Apresenta uma lista de opções para o usuário escolher. |
| `<option>` | Define uma opção disponível dentro de `<select>`. |
| `<textarea>` | Recebe textos maiores, como descrições, mensagens e biografias. |
| `<button>` | Executa uma ação de formulário, como enviar, salvar ou confirmar uma operação. |
| `<fieldset>` | Agrupa controles relacionados; nas provas, delimita visualmente as alternativas de uma questão. |
| `<legend>` | Fornece o título ou enunciado do grupo criado por `<fieldset>`. |

## Tabelas

| Tag | Uso no projeto |
| --- | --- |
| `<table>` | Organiza dados tabulares, como listagens administrativas de alunos, cursos ou matrículas. |
| `<thead>` | Agrupa a linha de cabeçalho de uma tabela. |
| `<tbody>` | Agrupa as linhas com os registros da tabela. |
| `<tr>` | Define uma linha da tabela. |
| `<th>` | Define uma célula de cabeçalho que identifica uma coluna ou linha. |
| `<td>` | Define uma célula com os dados de um registro. |
