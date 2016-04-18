<?php
$a = 'function ' . ($node->byRef ? '&' : '') . $node->name . $this->pCommaSeparatedLines($node->params, '(', ')') . (null !== $node->returnType ? ' : ' . $this->pType($node->returnType) : '') . ' {' . $this->pStmts($node->stmts) . "\n" . '}';
