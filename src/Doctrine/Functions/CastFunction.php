<?php

namespace  App\Doctrine\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\TokenType;

class CastFunction extends FunctionNode {
    public $type = null;
    public $expression = null;

    public function parse(\Doctrine\ORM\Query\Parser $parser):void {
        $parser->match(TokenType::T_IDENTIFIER); // CAST
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->expression = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_AS);
        $this->type = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    /*
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker):string {
        return 'CAST(' . $this->field->dispatch($sqlWalker) . ' AS ' .
            $this->type->dispatch($sqlWalker) . ')';
    }
    */

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker):string
    {
        return sprintf(
            'CAST(%s AS %s)',
            $this->expression->dispatch($sqlWalker),
            trim($this->type->dispatch($sqlWalker), "'") // Strip quotes
        );
    }
}
