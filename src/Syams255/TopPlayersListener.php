<?php
	namespace Syams255;
	use Syams255\TopPlayers;
	use pocketmine\event\Listener;
	use pocketmine\event\block\BlockBreakEvent;
	use pocketmine\event\block\BlockPlaceEvent;
	use pocketmine\event\player\PlayerJoinEvent;
	use pocketmine\event\player\PlayerDeathEvent;
	use pocketmine\event\entity\EntityDamageByEntityEvent;
	use pocketmine\Player;
	class TopPlayersListener extends TopPlayers implements Listener {
		public function __construct(TopPlayers $plugin) {
			$this->atp = $plugin;
		}
		public function onPlayerJoin(PlayerJoinEvent $event) {
			$player = $event->getPlayer();
			$name = strtolower($player->getName());
			$user = $this->atp->users->query("SELECT * FROM `users` WHERE `nickname` = '$name'")->fetchArray(SQLITE3_ASSOC);
			if($user === false) {
				$this->atp->users->query("INSERT INTO `users`(`nickname`) VALUES('$name')");
				$this->atp->getLogger()->info("§eplayer $name not found. Create stats for this player");
			}
			$this->atp->addParticle();
		}
		public function onPlayerDeath(PlayerDeathEvent $event) {
			$player = $event->getEntity();
			$name = strtolower($player->getName());
			$this->atp->users->query("UPDATE `users` SET `death` = death + 1 WHERE `nickname` = '$name'");
			if($player->getLastDamageCause() instanceof EntityDamageByEntityEvent) {
				$killer = $player->getLastDamageCause()->getDamager();
				if($killer instanceof Player) {
					$killer = strtolower($killer->getName());
					$this->atp->users->query("UPDATE `users` SET `kill` = kill + 1 WHERE `nickname` = '$killer'");
				}
			}
		}
		public function onBlockPlace(BlockPlaceEvent $event) {
			$player = strtolower($event->getPlayer()->getName());
			$this->atp->users->query("UPDATE `users` SET `place` = place + 1 WHERE `nickname` = '$player'");
		}
		public function onBreakPlace(BlockBreakEvent $event) {
			$player = strtolower($event->getPlayer()->getName());
			$this->atp->users->query("UPDATE `users` SET `break` = break + 1 WHERE `nickname` = '$player'");
		}
	}
?>
