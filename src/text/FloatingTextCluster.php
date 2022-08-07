<?php

declare(strict_types=1);

namespace jp\mcbe\fuyutsuki\Texter\text;

use jp\mcbe\fuyutsuki\Texter\data\Data;
use JsonSerializable;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\World;

class FloatingTextCluster implements Sendable, JsonSerializable {

	use Nameable {
		Nameable::__construct as nameableConstruct;
	}

	private Vector3 $spacing;
	/** @var FloatingText[] */
	private array $floatingTexts = [];

	public function __construct(
		private Vector3 $position,
		string $name,
		?Vector3 $spacing = null,
		array $texts = []
	) {
		$this->nameableConstruct($name);
		$this->setSpacing($spacing);
		$this->generateFloatingText($texts);
	}

	public function position(): Vector3 {
		return $this->position;
	}

	public function setPosition(Vector3 $position) {
		$this->position = $position;
	}

	public function spacing(): Vector3 {
		return $this->spacing;
	}

	public function setSpacing(?Vector3 $spacing = null) {
		$this->spacing = $spacing ?? Vector3::zero();
	}

	public function calculateSpacing(int $index): Vector3 {
		return $this->position->addVector($this->spacing->multiply($index));
	}

	public function recalculatePosition() {
		foreach ($this->floatingTexts as $index => $floatingText) {
			$floatingText->setPosition($this->calculateSpacing($index));
		}
	}

	public function generateFloatingText(array $texts) {
		foreach ($texts as $index => $text) {
			$this->floatingTexts[] = new FloatingText(
				$this->calculateSpacing($index),
				$text,
				$this
			);
		}
	}

	public function all(): array {
		return $this->floatingTexts;
	}

	public function get(int $index): ?FloatingText {
		return $this->floatingTexts[$index];
	}

	public function append(FloatingText $floatingText) {
		$this->floatingTexts[] = $floatingText;
	}

	public function update(int $index, FloatingText $floatingText) {
		$this->floatingTexts[$index] = $floatingText;
	}

	public function remove(int $index) {
		unset($this->floatingTexts[$index]);
	}

	public function resetIndex() {
		$this->floatingTexts = array_values($this->floatingTexts);
	}

	public function sendToPlayer(Player $player, SendType $type): void {
		foreach ($this->floatingTexts as $floatingText) {
			$floatingText->sendToPlayer($player, $type);
		}
	}

	public function sendToPlayers(array $players, SendType $type): void {
		foreach ($this->floatingTexts as $floatingText) {
			$floatingText->sendToPlayers($players, $type);
		}
	}

	public function sendToWorld(World $world, SendType $type): void {
		foreach ($this->floatingTexts as $floatingText) {
			$floatingText->sendToWorld($world, $type);
		}
	}

	public function jsonSerialize(): array {
		$rounded = $this->position->round(1);
		$result = [
			Data::KEY_X => $rounded->x,
			Data::KEY_Y => $rounded->y,
			Data::KEY_Z => $rounded->z,
		];
		if (count($this->floatingTexts) >= 2) {
			$result[Data::KEY_SPACING] = [
				Data::KEY_X => $this->spacing->x,
				Data::KEY_Y => $this->spacing->y,
				Data::KEY_Z => $this->spacing->z,
			];
		}
		$this->resetIndex();
		foreach ($this->floatingTexts as $floatingText) {
			$result[Data::KEY_TEXTS][] = $floatingText->text();
		}
		return $result;
	}

	public static function fromArray(string $name, array $arr): self {
		$spacing = null;
		if (isset($arr[Data::KEY_SPACING])) {
			$spacing = new Vector3(
				$arr[Data::KEY_SPACING][Data::KEY_X],
				$arr[Data::KEY_SPACING][Data::KEY_Y],
				$arr[Data::KEY_SPACING][Data::KEY_Z]
			);
		}
		return new self(
			new Vector3(
				$arr[Data::KEY_X],
				$arr[Data::KEY_Y],
				$arr[Data::KEY_Z]
			),
			$name,
			$spacing,
			$arr[Data::KEY_TEXTS]
		);
	}

}