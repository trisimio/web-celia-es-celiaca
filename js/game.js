/* ═══════════════════════════════════════════════════════
   MUERTE AL PAN — Easter Egg Runner Game
   Celia esquiva briznas de trigo
   ═══════════════════════════════════════════════════════ */

(function () {
  'use strict';

  var overlay = document.getElementById('gameOverlay');
  var canvas = document.getElementById('gameCanvas');
  var ctx = canvas.getContext('2d');
  var scoreEl = document.getElementById('gameScore');
  var hintEl = document.getElementById('gameHint');
  var closeBtn = document.getElementById('gameClose');
  var trigger = document.getElementById('easterEgg');

  var W = 600;
  var H = 200;
  var GROUND = H - 30;
  var GRAVITY = 0.6;
  var JUMP_FORCE = -11;
  var BASE_SPEED = 4;

  var celia = { x: 60, y: GROUND, vy: 0, w: 24, h: 40, jumping: false };
  var obstacles = [];
  var score = 0;
  var highScore = 0;
  var gameRunning = false;
  var gameOver = false;
  var frameId = null;
  var spawnTimer = 0;
  var speed = BASE_SPEED;

  function drawCelia() {
    var x = celia.x;
    var y = celia.y - celia.h;

    // Head
    ctx.fillStyle = '#FBA4A2';
    ctx.beginPath();
    ctx.arc(x + 12, y + 8, 8, 0, Math.PI * 2);
    ctx.fill();

    // Hair
    ctx.fillStyle = '#2a1a0a';
    ctx.beginPath();
    ctx.arc(x + 12, y + 5, 8, Math.PI, Math.PI * 2);
    ctx.fill();
    ctx.fillRect(x + 4, y + 2, 3, 12);
    ctx.fillRect(x + 17, y + 2, 3, 12);

    // Body
    ctx.fillStyle = '#D946C8';
    ctx.fillRect(x + 7, y + 16, 10, 14);

    // Arms
    ctx.strokeStyle = '#FBA4A2';
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(x + 7, y + 20);
    ctx.lineTo(x + 1, y + 28);
    ctx.moveTo(x + 17, y + 20);
    ctx.lineTo(x + 23, y + 28);
    ctx.stroke();

    // Legs
    ctx.strokeStyle = '#333';
    ctx.lineWidth = 2.5;
    var legAnim = gameRunning && !celia.jumping ? Math.sin(Date.now() / 80) * 4 : 0;
    ctx.beginPath();
    ctx.moveTo(x + 9, y + 30);
    ctx.lineTo(x + 6 + legAnim, y + 40);
    ctx.moveTo(x + 15, y + 30);
    ctx.lineTo(x + 18 - legAnim, y + 40);
    ctx.stroke();

    // Guitar on back
    ctx.fillStyle = '#8A59F8';
    ctx.fillRect(x + 18, y + 12, 3, 16);
    ctx.beginPath();
    ctx.arc(x + 19, y + 30, 4, 0, Math.PI * 2);
    ctx.fill();
  }

  function drawWheat(ob) {
    var x = ob.x;
    var baseY = GROUND;
    var h = ob.h;

    // Stem
    ctx.strokeStyle = '#C4A84D';
    ctx.lineWidth = 2.5;
    ctx.beginPath();
    ctx.moveTo(x + 6, baseY);
    ctx.lineTo(x + 6, baseY - h);
    ctx.stroke();

    // Wheat head (grains)
    ctx.fillStyle = '#DAB94E';
    for (var i = 0; i < 5; i++) {
      var gy = baseY - h + i * 5;
      ctx.beginPath();
      ctx.ellipse(x + 3, gy + 2, 4, 2.5, -0.3, 0, Math.PI * 2);
      ctx.fill();
      ctx.beginPath();
      ctx.ellipse(x + 9, gy + 4, 4, 2.5, 0.3, 0, Math.PI * 2);
      ctx.fill();
    }

    // Awns (whiskers)
    ctx.strokeStyle = '#B89A3E';
    ctx.lineWidth = 1;
    for (var j = 0; j < 3; j++) {
      var ay = baseY - h + j * 6;
      ctx.beginPath();
      ctx.moveTo(x + 3, ay);
      ctx.lineTo(x - 4, ay - 8);
      ctx.moveTo(x + 9, ay + 2);
      ctx.lineTo(x + 16, ay - 6);
      ctx.stroke();
    }
  }

  function drawGround() {
    ctx.fillStyle = '#333';
    ctx.fillRect(0, GROUND, W, H - GROUND);

    // Ground line
    ctx.strokeStyle = '#555';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(0, GROUND);
    ctx.lineTo(W, GROUND);
    ctx.stroke();
  }

  function drawGameOver() {
    ctx.fillStyle = 'rgba(8, 8, 12, 0.7)';
    ctx.fillRect(0, 0, W, H);

    ctx.fillStyle = '#FBA4A2';
    ctx.font = 'bold 28px "Bebas Neue", sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText('GAME OVER', W / 2, H / 2 - 10);

    ctx.fillStyle = '#B0B0C0';
    ctx.font = '14px "Space Grotesk", sans-serif';
    ctx.fillText('Toca o pulsa espacio para reintentar', W / 2, H / 2 + 18);

    if (highScore > 0) {
      ctx.fillStyle = '#8A59F8';
      ctx.font = '12px "JetBrains Mono", monospace';
      ctx.fillText('RECORD: ' + highScore, W / 2, H / 2 + 40);
    }
  }

  function drawStartScreen() {
    ctx.fillStyle = '#111118';
    ctx.fillRect(0, 0, W, H);
    drawGround();
    celia.y = GROUND;
    drawCelia();

    // Draw a wheat as preview
    drawWheat({ x: W - 80, h: 35 });

    ctx.fillStyle = '#FBA4A2';
    ctx.font = 'bold 22px "Bebas Neue", sans-serif';
    ctx.textAlign = 'center';
    ctx.fillText('MUERTE AL PAN!', W / 2, 50);

    ctx.fillStyle = '#B0B0C0';
    ctx.font = '13px "Space Grotesk", sans-serif';
    ctx.fillText('Espacio o toca para empezar', W / 2, 80);
  }

  function spawnObstacle() {
    var h = 25 + Math.random() * 25;
    obstacles.push({ x: W + 10, h: h, w: 14, scored: false });
  }

  function reset() {
    celia.y = GROUND;
    celia.vy = 0;
    celia.jumping = false;
    obstacles = [];
    score = 0;
    speed = BASE_SPEED;
    spawnTimer = 0;
    scoreEl.textContent = '0';
    gameOver = false;
    gameRunning = true;
    hintEl.textContent = 'Espacio o toca para saltar';
  }

  function jump() {
    if (!celia.jumping) {
      celia.vy = JUMP_FORCE;
      celia.jumping = true;
    }
  }

  function update() {
    // Physics
    celia.vy += GRAVITY;
    celia.y += celia.vy;
    if (celia.y >= GROUND) {
      celia.y = GROUND;
      celia.vy = 0;
      celia.jumping = false;
    }

    // Spawn
    spawnTimer++;
    var interval = Math.max(50, 100 - score * 2);
    if (spawnTimer >= interval) {
      spawnObstacle();
      spawnTimer = 0;
    }

    // Speed increases
    speed = BASE_SPEED + score * 0.15;

    // Move obstacles
    for (var i = obstacles.length - 1; i >= 0; i--) {
      obstacles[i].x -= speed;

      // Score
      if (!obstacles[i].scored && obstacles[i].x + obstacles[i].w < celia.x) {
        obstacles[i].scored = true;
        score++;
        scoreEl.textContent = score;
      }

      // Collision
      var ob = obstacles[i];
      var celiaTop = celia.y - celia.h;
      var obTop = GROUND - ob.h;
      if (
        celia.x + celia.w - 6 > ob.x &&
        celia.x + 6 < ob.x + ob.w &&
        celia.y > obTop + 5
      ) {
        gameRunning = false;
        gameOver = true;
        if (score > highScore) highScore = score;
        hintEl.textContent = 'Record: ' + highScore;
      }

      // Cleanup
      if (ob.x + ob.w < -20) {
        obstacles.splice(i, 1);
      }
    }
  }

  function draw() {
    ctx.clearRect(0, 0, W, H);
    ctx.fillStyle = '#111118';
    ctx.fillRect(0, 0, W, H);

    drawGround();

    for (var i = 0; i < obstacles.length; i++) {
      drawWheat(obstacles[i]);
    }

    drawCelia();

    if (gameOver) {
      drawGameOver();
    }
  }

  function loop() {
    if (gameRunning) {
      update();
    }
    draw();
    frameId = requestAnimationFrame(loop);
  }

  function handleInput() {
    if (gameOver) {
      reset();
    } else if (gameRunning) {
      jump();
    } else {
      reset();
    }
  }

  function openGame() {
    overlay.hidden = false;
    overlay.offsetHeight;
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    drawStartScreen();
    if (!frameId) loop();
  }

  function closeGame() {
    overlay.classList.remove('active');
    document.body.style.overflow = '';
    gameRunning = false;
    if (frameId) {
      cancelAnimationFrame(frameId);
      frameId = null;
    }
    setTimeout(function () { overlay.hidden = true; }, 300);
  }

  // Event listeners
  trigger.addEventListener('click', openGame);
  closeBtn.addEventListener('click', closeGame);

  document.addEventListener('keydown', function (e) {
    if (!overlay.classList.contains('active')) return;
    if (e.code === 'Space' || e.code === 'ArrowUp') {
      e.preventDefault();
      handleInput();
    }
    if (e.code === 'Escape') closeGame();
  });

  canvas.addEventListener('click', handleInput);
  canvas.addEventListener('touchstart', function (e) {
    e.preventDefault();
    handleInput();
  }, { passive: false });
})();
