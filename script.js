const stateText = document.getElementById("state-text");
const buttonSpin = document.getElementById("button-spin");
const slots = document.querySelectorAll(".slot img");
const score = document.getElementById("score");
const bet = document.getElementById("bet");

const spinSound = new Audio("assets/audio/spin.mp3");
const handSound = new Audio("assets/audio/hand.mp3");
const soundLose = new Audio("assets/audio/lose.mp3");
const soundWin = new Audio("assets/audio/win.mp3");
const backAudio = new Audio("assets/audio/back.mp3");

spinSound.loop = true;
backAudio.loop = true;

spinSound.volume = 0.2;
handSound.volume = 0.4;
soundLose.volume = 0.5;
soundWin.volume = 0.4;
backAudio.volume = 0.03;

let backStarted = false;

const slotImages = ["seven", "cherry", "lemon", "bar"];

let balance = Number(localStorage.getItem("balance"));

if (localStorage.getItem("balance") === null) {
  balance = 10000;
  localStorage.setItem("balance", balance);
}

score.textContent = balance;

function spinSlot(slot) {
  return setInterval(() => {
    const randomIndex = Math.floor(Math.random() * slotImages.length);

    slot.src = `assets/img/${slotImages[randomIndex]}.png`;
  }, 100);
}

buttonSpin.addEventListener("click", async () => {
  try {
    if (Number(bet.value) > Number(score.textContent)) {
      return;
    }

    buttonSpin.disabled = true;

    if (!backStarted) {
      backAudio.currentTime = 0;
      await backAudio.play();
      backStarted = true;
    }

    handSound.currentTime = 0;
    await handSound.play();

    const animations = [];

    for (const slot of slots) {
      animations.push(spinSlot(slot));
    }

    stateText.textContent = "TRY YOUR LUCK!";

    spinSound.currentTime = 0;
    spinSound.play();

    const responsePromise = fetch("spin.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        bet: Number(bet.value),
        size: 3,
      }),
    });

    balance -= Number(bet.value);
    score.textContent = balance;

    await new Promise((resolve) => setTimeout(resolve, 5000));

    spinSound.pause();
    spinSound.currentTime = 0;

    const response = await responsePromise;

    if (!response.ok) {
      console.error("HTTP ошибка:", response.status);
      return;
    }

    const data = await response.json();

    console.debug("Ответ сервера:", data);

    for (const animation of animations) {
      clearInterval(animation);
    }

    for (let indexSlot = 0; indexSlot < slots.length; indexSlot++) {
      slots[indexSlot].src = `assets/img/${data.slots[indexSlot]}.png`;
    }

    balance += Number(data.win);

    score.textContent = balance;

    if (Number(data.win) === 0) {
      soundLose.currentTime = 0;
      await soundLose.play();

      stateText.textContent = "YOU LOSE!!!";
    } else {
      soundWin.currentTime = 0;
      await soundWin.play();

      stateText.textContent = "YOU WIN!!!";
    }
  } catch (error) {
    console.error("Ошибка:", error);
  } finally {
    localStorage.setItem("balance", balance);
    buttonSpin.disabled = false;
  }
});
