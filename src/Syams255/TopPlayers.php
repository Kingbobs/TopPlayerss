<?php
	namespace Syams255;
	use pocketmine\plugin\PluginBase;
	use pocketmine\utils\Config;
	use pocketmine\math\Vector3;
	use pocketmine\level\particle\FloatingTextParticle;
	use pocketmine\command\Command;
	use pocketmine\command\CommandSender;
	use pocketmine\event\Listener;
	use pocketmine\Player;
	use pocketmine\Server;
	class TopPlayers extends PluginBase implements Listener {
		public $config, $users, $particles;
		public function onEnable() {
			$folder = $this->getDataFolder();
			if(!is_dir($folder))
				@mkdir($folder);
			$this->saveResource('config.yml');
			$this->config = (new Config($folder.'config.yml', Config::YAML))->getAll();
			$this->users = new \SQLite3($folder.'statistics.db');
			$this->users->exec("CREATE TABLE IF NOT EXISTS users(
														nickname TEXT PRIMARY KEY NOT NULL,
														kill INTEGER default 0 NOT NULL,
														death INTEGER default 0 NOT NULL,
														place INTEGER default 0 NOT NULL,
														break INTEGER default 0 NOT NULL
													);
												");
			unset($folder);
			$this->getServer()->getPluginManager()->registerEvents(new aTopPlayersListener($this), $this);
		}
		public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
			if($sender instanceof Player) {
				if(strtolower($command->getName()) == 'top') {
					if(count($args) == 1 || count($args) == 2) {
						$action = strtolower($args[0]);
						if($action == 'kill' || $action == 'death' || $action == 'break' || $action == 'place') {
							$data = [
								'type' => $action,
								'x' => round($sender->getX()),
								'y' => round($sender->getY()),
								'z' => round($sender->getZ()),
								'level' => strtolower($sender->getLevel()->getName())
							];
							$this->save($data);
							$this->create($action);
							return true;
						}
						$user = $this->users->query("SELECT * FROM `users` WHERE `nickname` = '$action'")->fetchArray(SQLITE3_ASSOC);
						if($user !== false)
							$sender->sendMessage(str_replace('{player}', $action, $this->config['titleStat'])."\n§aУбил: §d".$user['kill']."\n§aУмер: §d".$user['death']."\n§aСломано: §d".$user['break']."\n§aПоставил: §d".$user['place']);
						else $sender->sendMessage($this->config['userNotExist']);
					}
					else $sender->sendMessage('§eUsing: /top <kill/death/break/place> [player]');
				}
			}
		}
		public function addParticle() {
			if(count($this->config['coords']) > 0)
				foreach($this->config['coords'] as $type => $stat)
					$this->create($type);
		}
		/**
		 * @param string $type
		 */
		private function create($type) {
			$coords = $this->config['coords'][$type];
			$vector3 = new Vector3($coords['x'], $coords['y'], $coords['z']);
			$list = $this->sort($type);
			$this->particles[$type] = new FloatingTextParticle($vector3, $list, $this->config[$type.'Title']);
			$this->getServer()->getLevelByName($this->config['coords'][$type]['level'])->addParticle($this->particles[$type]);
		}
		/**
		 * @param string  $type
		 * @return string $list
		 */
		public function sort($type) {
			$limit = $this->config[$type.'Count'];
			$top = $this->users->query("SELECT nickname,$type FROM `users` ORDER BY $type DESC LIMIT $limit");
			$list = "";
			while($element = $top->fetchArray(SQLITE3_ASSOC))
				$list .= str_replace(['{player}', '{value}'], [$element['nickname'], $element[$type]], $this->config[$type.'Element'])."\n";
			return $list;
		}
		/**
		 * @param array $write
		 */
		private function save($write = false) {
			if($write !== false) {
				$this->config['coords'][$write['type']] = [
					'x' => $write['x'],
					'y' => $write['y'] + 2.5,
					'z' => $write['z'],
					'level' => $write['level']
				];
			}
			$cfg = new Config($this->getDataFolder().'config.yml', Config::YAML);
			$cfg->setAll($this->config);
			$cfg->save();
			unset($cfg);
		}
	}
?>
