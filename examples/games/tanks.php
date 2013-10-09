<?php
	$title = "Tanks";
	require('../head.php');
?>

<script type="text/javascript">

	EnemyTank = function (index, game, player, bullets) {

		var x = game.world.randomX;
		var y = game.world.randomY;

		this.game = game;
		this.health = 3;
		this.player = player;
		this.bullets = bullets;
		this.fireRate = 1000;
		this.nextFire = 0;
		this.alive = true;

		this.shadow = game.add.sprite(x, y, 'enemy', 'shadow');
		this.tank = game.add.sprite(x, y, 'enemy', 'tank1');
		this.turret = game.add.sprite(x, y, 'enemy', 'turret');

	    this.shadow.anchor.setTo(0.5, 0.5);
	    this.tank.anchor.setTo(0.5, 0.5);
	    this.turret.anchor.setTo(0.3, 0.5);

	    this.tank.name = index.toString();
      	this.tank.body.immovable = true;
      	this.tank.body.collideWorldBounds = true;
      	this.tank.body.bounce.setTo(1, 1);

      	this.tank.angle = game.rnd.angle();

		game.physics.velocityFromRotation(this.tank.rotation, 100, this.tank.body.velocity);

	};

	EnemyTank.prototype.damage = function() {

		this.health -= 1;

		if (this.health <= 0)
		{
			this.alive = false;

			this.shadow.kill();
			this.tank.kill();
			this.turret.kill();

			return true;
		}

		return false;

	}

	EnemyTank.prototype.update = function() {

		this.shadow.x = this.tank.x;
		this.shadow.y = this.tank.y;
		this.shadow.rotation = this.tank.rotation;

		this.turret.x = this.tank.x;
		this.turret.y = this.tank.y;
		this.turret.rotation = this.game.physics.angleBetween(this.tank, this.player);

		if (this.game.physics.distanceBetween(this.tank, this.player) < 300)
		{
			if (this.game.time.now > this.nextFire && this.bullets.countDead() > 0)
			{
				this.nextFire = this.game.time.now + this.fireRate;

				var bullet = this.bullets.getFirstDead();

				bullet.reset(this.turret.x, this.turret.y);

				bullet.rotation = this.game.physics.moveToObject(bullet, this.player, 500);
			}
		}

	};

	var game = new Phaser.Game(800, 600, Phaser.CANVAS, '', { preload: preload, create: create, update: update, render: render });

	function preload () {

		game.load.atlas('tank', 'assets/games/tanks/tanks.png', 'assets/games/tanks/tanks.json');
		game.load.atlas('enemy', 'assets/games/tanks/enemy-tanks.png', 'assets/games/tanks/tanks.json');
		game.load.image('bullet', 'assets/games/tanks/bullet.png');
		game.load.image('earth', 'assets/games/tanks/scorched_earth.png');
		game.load.spritesheet('explosion', 'assets/games/tanks/explosion.png', 64, 64, 23);
		
	}

	var land;

	var shadow;
	var tank;
	var turret;

	var enemies;
	var enemyBullets;
	var explosions;

	var currentSpeed = 0;
	var cursors;

	var bullets;
	var fireRate = 100;
	var nextFire = 0;

	function create () {

		//	Resize our game world to be a 2000 x 2000 square
		game.world.setBounds(-1000, -1000, 2000, 2000);

		//	Our tiled scrolling background
		land = game.add.tileSprite(0, 0, 800, 600, 'earth');
		land.fixedToCamera = true;

		//	The base of our tank
		tank = game.add.sprite(0, 0, 'tank', 'tank1');
		tank.anchor.setTo(0.5, 0.5);
		tank.animations.add('move', ['tank1', 'tank2', 'tank3', 'tank4', 'tank5', 'tank6'], 20, true);
      	// tank.play('move');

      	//	This will force it to decelerate and limit its speed
      	tank.body.drag.setTo(200, 200);
      	tank.body.maxVelocity.setTo(400, 400);
      	tank.body.collideWorldBounds = true;

		//	Finally the turret that we place on-top of the tank body
		turret = game.add.sprite(0, 0, 'tank', 'turret');
		turret.anchor.setTo(0.3, 0.5);

      	//	The enemies bullet group
		enemyBullets = game.add.group();
		enemyBullets.createMultiple(100, 'bullet');
		enemyBullets.setAll('anchor.x', 0.5);
		enemyBullets.setAll('anchor.y', 0.5);
		enemyBullets.setAll('outOfBoundsKill', true);

		//	Create some baddies to waste :)
		enemies = [];

		for (var i = 0; i < 10; i++)
		{
			enemies.push(new EnemyTank(i, game, tank, enemyBullets));
		}

		//	A shadow below our tank
		shadow = game.add.sprite(0, 0, 'tank', 'shadow');
		shadow.anchor.setTo(0.5, 0.5);

      	//	Our bullet group
		bullets = game.add.group();
		bullets.createMultiple(30, 'bullet');
		bullets.setAll('anchor.x', 0.5);
		bullets.setAll('anchor.y', 0.5);
		bullets.setAll('outOfBoundsKill', true);

		//	Explosion pool
		explosions = game.add.group();

		for (var i = 0; i < 10; i++)
		{
			var e = explosions.create(0, 0, 'explosion', 0, false);
			e.animations.add('boom');
		}

		tank.bringToTop();
		turret.bringToTop();

		game.camera.follow(tank);
		game.camera.deadzone = new Phaser.Rectangle(100, 100, 600, 400);
		game.camera.focusOnXY(0, 0);

		cursors = game.input.keyboard.createCursorKeys();

	}

	function update () {

		game.physics.collide(enemyBullets, tank, bulletHitPlayer, null, this);

		for (var i = 0; i < enemies.length; i++)
		{
			if (enemies[i].alive)
			{
				enemies[i].update();
				game.physics.collide(tank, enemies[i].tank);
				game.physics.collide(bullets, enemies[i].tank, bulletHitEnemy, null, this);
			}
		}

        if (cursors.left.isDown)
        {
        	tank.angle -= 4;
        }
        else if (cursors.right.isDown)
        {
        	tank.angle += 4;
        }

        if (cursors.up.isDown)
        {
        	//	The speed we'll travel at
        	currentSpeed = 300;
        }
        else
        {
        	if (currentSpeed > 0)
        	{
	        	currentSpeed -= 4;
        	}
        }

    	if (currentSpeed > 0)
    	{
	        game.physics.velocityFromRotation(tank.rotation, currentSpeed, tank.body.velocity);
    	}

        land.tilePosition.x = -game.camera.x;
        land.tilePosition.y = -game.camera.y;

        //	Position all the parts and align rotations
		shadow.x = tank.x;
		shadow.y = tank.y;
		shadow.rotation = tank.rotation;

		turret.x = tank.x;
		turret.y = tank.y;

		turret.rotation = game.physics.angleToPointer(turret);

		if (game.input.activePointer.isDown)
		{
			//	Boom!
			fire();
		}

	}

	function bulletHitPlayer (tank, bullet) {

		bullet.kill();


	}

	function bulletHitEnemy (tank, bullet) {

		bullet.kill();

		var destroyed = enemies[tank.name].damage();

		if (destroyed)
		{
			var e = explosions.getFirstDead();
			e.reset(tank.x, tank.y);
			e.play('boom');
		}

	}

	function fire () {

		if (game.time.now > nextFire && bullets.countDead() > 0)
		{
			nextFire = game.time.now + fireRate;

			var bullet = bullets.getFirstDead();

			bullet.reset(turret.x, turret.y);

			bullet.rotation = game.physics.moveToPointer(bullet, 1000);
		}

	}

	function render () {

        // game.debug.renderText('Active Bullets: ' + bullets.countLiving() + ' / ' + bullets.total, 32, 32);

	}

</script>

<?php
	require('../foot.php');
?>