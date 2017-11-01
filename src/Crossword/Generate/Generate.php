<?php

namespace Crossword\Generate;

use \Crossword\Crossword;
use \Crossword\Word;

/**
 * Базовый класс генерации кроссворда
 *
 * Для создания нового типа генерации нужно:
 *  1. Cоздать новый класс унаследованный от текущего.
 *  2. Написать функции расположения первого слова. @see $this->_positionFirstWord();
 *  3. Написать функции расположения остальных слов. @see $this->_positionWord($word);
 *
 * Что бы расположить слово на строке или колонке нужно вызвать метод CrosswordLine::position();
 */
abstract class Generate
{

    /**
     * Max count of crossword generation
     */
    const MAX_GENERATE_ATTEMPTS = 100;

    /**
     * Max count of words positioning
     */
    const MAX_WORD_POSITION_ATTEMPTS = 100;

    /**
     * Тип генерации. Рандомный
     */
    const TYPE_RANDOM = 'random';

    /**
     * Тип генерации. На основе одного слова по вертикали
     */
    const TYPE_BASE_LINE_COLUMN = 'baseLine\\Column';

    /**
     * Тип генерации. На основе одного слова по горизонтали
     */
    const TYPE_BASE_LINE_ROW = 'baseLine\\Row';

    /**
     * Тип генерации. На основе одного числа
     */
    const TYPE_SEED = 'seed';

    /**
     * @var \Crossword\Crossword
     */
    protected $crossword;

    /**
     * @param \Crossword\Crossword $crossword
     */
    public function __construct(Crossword $crossword)
    {
        $this->crossword = $crossword;
    }

    /**
     * @param bool $needAllWords Generate crossword with all words
     * @param int $maxGenerateAttempts Max count of crossword generation
     * @param int $maxWordPositionAttempts Max count of words positioning
     *
     * @return bool Return true if crossword is generated
     */
    public function generate(
        $needAllWords = false,
        $maxGenerateAttempts = self::MAX_GENERATE_ATTEMPTS,
        $maxWordPositionAttempts = self::MAX_WORD_POSITION_ATTEMPTS
    ) {
        while ($maxGenerateAttempts != 0) {
            $crossword = $this->crossword;
            $isPosition = $this->positionFirstWord();
            $maxWordPositionAttemptsInGenerate = $maxWordPositionAttempts;

            if (!$isPosition) {
                $maxGenerateAttempts--;
                $this->crossword->clear();
                continue;
            }

            while ($maxWordPositionAttemptsInGenerate != 0) {
                $words = $crossword->getWords()->notUsed();

                if ($words->notEmpty()) {
                    $this->positionWord($words->getRandom());
                } else {
                    break;
                }

                $maxWordPositionAttemptsInGenerate--;
            }

            // If need all words and we have not used - regenerate crossword
            if ($needAllWords && count($crossword->getWords()->notUsed())) {
                $maxGenerateAttempts--;
                $this->crossword->clear();
                continue;
            }

            return true;
        }

        if ($needAllWords && count($this->crossword->getWords()->notUsed())) {
            return false;
        }

        return true;
    }

    /**
     * Функция расположения первого слова
     *
     * @abstract
     */
    abstract protected function positionFirstWord();

    /**
     * Функции расположения остальных слов
     *
     * @abstract
     * @param Word $word
     * @return
     */
    abstract protected function positionWord(Word $word);

    /**
     * @static
     * @param string $generateType SELF::TYPE_*
     * @param \Crossword\Crossword $crossword
     * @return Generate
     * @throws \Exception
     */
    static public function factory($generateType, Crossword $crossword)
    {
        $generateType = ucfirst($generateType);
        $className = 'Crossword\\Generate\\' . $generateType;
        if(class_exists($className)) {
            return new $className($crossword);
        }
        throw new \Exception('Не найден класс ' . $className);
    }

    /**
     * @return mixed CrosswordLineRow
     */
    protected function getCenterRow()
    {
        return $this->crossword->getRows()->getByIndex((int) round($this->crossword->getRowsCount() / 2));
    }

    /**
     * @return mixed CrosswordLineCol
     */
    protected function getCenterCol()
    {
        return $this->crossword->getColumns()->getByIndex((int) round($this->crossword->getColumnsCount() / 2));
    }

}