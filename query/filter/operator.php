<?php
/**
 * Bitrix Framework
 * @package    bitrix
 * @subpackage main
 * @copyright  2001-2016 Bitrix
 */

namespace Bitrix\Main\ORM\Query\Filter;
use Bitrix\Main\ORM\Query\Filter\Expressions\NullExpression;

/**
 * SQL operators handler.
 * @package    bitrix
 * @subpackage main
 */
class Operator
{
	/**
	 * Available operators.
	 * @var array
	 */
	protected static array $operators = array(
		'=' => 'eq',
		'<>' => 'neq',
		'!=' => 'neq',
		'<' => 'lt',
		'<=' => 'lte',
		'>' => 'gt',
		'>=' => 'gte',
		'in' => 'in',
		'between' => 'between',
		'like' => 'like',
		'exists' => 'exists',
		'match' => 'match',
		'expr' => 'expr'
	);

	/**
	 * List of available operators `code => method`.
	 *
	 * @return array
	 */
	public static function get(): array
    {
		return static::$operators;
	}

	public static function eq($columnSql, $valueSql): string
    {
		if ($valueSql instanceof NullExpression)
		{
			return "{$columnSql} IS NULL";
		}
		return "{$columnSql} = {$valueSql}";
	}

	public static function neq($columnSql, $valueSql): string
    {
		if ($valueSql instanceof NullExpression)
		{
			return "{$columnSql} IS NOT NULL";
		}
		return "{$columnSql} <> {$valueSql}";
	}

	public static function lt($columnSql, $valueSql): string
    {
		return "{$columnSql} < {$valueSql}";
	}

	public static function lte($columnSql, $valueSql)
	{
		return "{$columnSql} <= {$valueSql}";
	}

	public static function gt($columnSql, $valueSql): string
    {
		return "{$columnSql} > {$valueSql}";
	}

	public static function gte($columnSql, $valueSql): string
    {
		return "{$columnSql} >= {$valueSql}";
	}

	public static function in($columnSql, $valueSql): string
    {
		return "{$columnSql} IN (".join(', ', (array) $valueSql).")";
	}

	public static function between($columnSql, $valueSql): string
    {
		return "{$columnSql} BETWEEN {$valueSql[0]} AND {$valueSql[1]}";
	}

	public static function like($columnSql, $valueSql): string
    {
		return "{$columnSql} LIKE {$valueSql}";
	}

	public static function exists($columnSql, $valueSql): string
    {
		return "EXISTS ({$valueSql})";
	}

	public static function match($columnSql, $valueSql): string
    {
		$connection = \Bitrix\Main\Application::getConnection();
		$helper = $connection->getSqlHelper();

		return $helper->getMatchFunction($columnSql, $valueSql);
	}

	public static function expr($columnSql, $valueSql): string
    {
		return "{$columnSql}";
	}
}
