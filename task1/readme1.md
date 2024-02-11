Implementační dokumentace k 1. úloze do IPP 2023/2024<br/>
Jméno a příjmení: Martin Slezák<br/>
Login: xsleza26

## Lexer

Lexer při vytvoření dostane kód, který následně postupně rozděluje na tokeny.
Po zavolání funkce `next()` vrátí další token. Čte znak po znaku a na základě
pravidel určuje, o jaký token se jedná. Při čtení vynechává bílé znaky a
komentáře, které následují za znakem mřížky (#)

Vytvořil jsem si několik typů tokenů. Pro proměnné (`VAR`), pro symboly (`INT`,
`STRING`, `BOOL`, `NIL`), pro typ (`type`) a také pro label (`LABEL`). Dále
také `NALABEL`, což je v podstatě label, který ale nesplňuje podmínku, že musí
začínat písmenem nebo povoleným speciálním znakem. Tento token je použit pro
povinou hlavičkou a v ostatních případech vede k chybě. Nakonec také tokeny
`EOL` a `EOF` značící konec řádku a konec souboru.

Proměnné a symboly mají podobná pravidla. Nejdříve lexer čte text a jakmile
narazí na znak zavináče (@), zkontroluje, zda text který doposud přečetl,
vyhovuje některému z definovaných datových typů nebo paměťovému rámci.

Pro symboly navíc zkontroluje, zda hodnota za zavináčem vyhovuje datovému typu
(pro int je to číslo, pro bool hodnota true/false,...)

Pro proměnné zkontroluje podmínku počátečního znaku, tedy že musí být buď
písmeno nebo platný speciální znak.

U tokenů `LABEL`, `NALABEL`, `TYPE` je to jednoduché. Jakmile lexer narazí na
bílý znak, ověří, zda přečtený text je jeden z definovaných datových typu
(`TYPE`), zda začíná povoleným znakem (`LABEL`) a jinak se jedná o `NALABEL`.

Token `EOL` je vrácen, jakmile je znak nový řádek a token `EOF` když se přečte
celý vstupní text obsahující kód.

## Parser

Parser si při vytvoření vytvoří lexer. Po zavolání jeho funkce `parse()` poté
postupně volá funkci lexeru `next()`. Na základě získaných tokenů rozhoduje,
zda se jedná o validní kód.

Po zavolání funkce `parse()` nejdříve parser ověří, zda kód obsahuje povinou
hlavičku. Následuje cyklus, který se opakuje dokud aktuální token není `EOF`,
tedy konec souboru.

V tomto cyklu se volá funkce `_parse_line()`, která zpracuje celý řádek, kdy
řádek musí obsahovat nejvýše jednu instrukci. Nejdříve ověří, zda na začátku
řádku je validní instrukce. Pokud ano, přidá instrukci do XML a zkontroluje
argumenty instrukce. Zkontroluje jak typ, který instrukce očekává, tak i počet
argumentů. Argumenty instrukcí po zkontrolování přidá do XML. Pro úspešné
zpracování řádku musí být poté na konci aktuální token `EOL` nebo `EOF` (konec
řádku nebo konec souboru)
