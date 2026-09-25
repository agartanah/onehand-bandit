const stateText = document.getElementById("state-text");
const buttonSpin = document.getElementById("button-spin");
const slots = document.querySelectorAll(".slot img");
const lockButtons = document.querySelectorAll(".slot-lock-button");
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

let balance = 0;
let serverBalance = 0;
let currentSlots = Array(slots.length).fill("seven");
const lockedSlots = Array(slots.length).fill(null);
let isSpinning = false;
let hasSpun = false;
let lockRequestInProgress = false;
let canLock = true;
let userLoaded = false;

function updateLockButtons() {
  const lockedCount = lockedSlots.filter(
    (slotName) => slotName !== null,
  ).length;

  lockButtons.forEach((button) => {
    const slotIndex = Number(button.dataset.slotIndex);
    const isLocked = lockedSlots[slotIndex] !== null;

    button.classList.toggle("is-locked", isLocked);
    button.setAttribute("aria-pressed", String(isLocked));
    button.disabled =
      !hasSpun ||
      !canLock ||
      isSpinning ||
      lockRequestInProgress ||
      (!isLocked && lockedCount >= 2);
  });
}

function clearLockedSlots() {
  lockedSlots.fill(null);
  updateLockButtons();
}

async function saveLockedSlots() {
  const lockedIndexes = lockedSlots.reduce((indexes, slotName, index) => {
    if (slotName !== null) {
      indexes.push(index);
    }

    return indexes;
  }, []);

  const response = await fetch("lock.php", {
    method: "POST",
    credentials: "same-origin",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      locked: lockedIndexes,
    }),
  });

  if (!response.ok) {
    const errorData = await response.json().catch(() => ({}));
    throw new Error(errorData.error || `HTTP ошибка: ${response.status}`);
  }
}

function spinSlot(slot) {
  return setInterval(() => {
    const randomIndex = Math.floor(Math.random() * slotImages.length);

    slot.src = `assets/img/${slotImages[randomIndex]}.png`;
  }, 100);
}

async function loadUser() {
  const responseUser = await fetch("user.php", {
    method: "GET",
    credentials: "same-origin",
  });

  if (!responseUser.ok) {
    throw new Error(`HTTP ошибка: ${responseUser.status}`);
  }

  const user = await responseUser.json();
  serverBalance = Number(user.balance);
  balance = serverBalance;
  canLock = user.canLock !== false;
  userLoaded = true;
  score.textContent = balance.toFixed(2);
}

buttonSpin.disabled = true;
updateLockButtons();

loadUser()
  .then(() => {
    buttonSpin.disabled = false;
  })
  .catch((error) => {
    console.error("Не удалось загрузить пользователя:", error);
    stateText.textContent = "CONNECTION ERROR";
  });

buttonSpin.addEventListener("click", async () => {
  const betValue = Number(bet.value);
  let animations = [];
  let betCharged = false;

  if (!Number.isFinite(betValue) || betValue <= 0 || betValue > balance) {
    return;
  }

  buttonSpin.disabled = true;
  isSpinning = true;
  updateLockButtons();

  try {
    if (!backStarted) {
      backAudio.currentTime = 0;
      await backAudio.play();
      backStarted = true;
    }

    handSound.currentTime = 0;
    await handSound.play();

    animations = Array.from(slots, (slot, index) =>
      lockedSlots[index] === null ? spinSlot(slot) : null,
    ).filter((animation) => animation !== null);
    stateText.textContent = "TRY YOUR LUCK!";

    spinSound.currentTime = 0;
    spinSound.play();

    balance -= betValue;
    score.textContent = balance.toFixed(2);
    betCharged = true;

    const responsePromise = fetch("spin.php", {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        bet: betValue,
        size: slots.length,
      }),
    });

    const [response] = await Promise.all([
      responsePromise,
      new Promise((resolve) => setTimeout(resolve, 5000)),
    ]);

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      throw new Error(errorData.error || `HTTP ошибка: ${response.status}`);
    }

    const data = await response.json();

    for (const animation of animations) {
      clearInterval(animation);
    }

    for (let indexSlot = 0; indexSlot < slots.length; indexSlot++) {
      slots[indexSlot].src = `assets/img/${data.slots[indexSlot]}.png`;
      currentSlots[indexSlot] = data.slots[indexSlot];
    }

    hasSpun = true;
    canLock = data.canLock === true;

    serverBalance = Number(data.balance);
    balance = serverBalance;
    score.textContent = balance.toFixed(2);
    clearLockedSlots();

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
    stateText.textContent = "SPIN FAILED";

    if (betCharged) {
      try {
        await loadUser();
      } catch (loadError) {
        console.error("Не удалось синхронизировать баланс:", loadError);
      }
    }
  } finally {
    for (const animation of animations) {
      clearInterval(animation);
    }

    spinSound.pause();
    spinSound.currentTime = 0;
    isSpinning = false;
    updateLockButtons();
    buttonSpin.disabled = false;
  }
});

lockButtons.forEach((button) => {
  button.addEventListener("click", async () => {
    if (isSpinning) {
      return;
    }

    const slotIndex = Number(button.dataset.slotIndex);
    const previousLockedSlots = lockedSlots.slice();

    if (lockedSlots[slotIndex] === null) {
      const lockedCount = lockedSlots.filter(
        (slotName) => slotName !== null,
      ).length;

      if (lockedCount >= 2) {
        return;
      }

      lockedSlots[slotIndex] = currentSlots[slotIndex];
    } else {
      lockedSlots[slotIndex] = null;
    }

    lockRequestInProgress = true;
    updateLockButtons();

    try {
      await saveLockedSlots();
    } catch (error) {
      lockedSlots.splice(0, lockedSlots.length, ...previousLockedSlots);
      stateText.textContent = "LOCK FAILED";
      console.error("Не удалось сохранить блокировку:", error);
    } finally {
      lockRequestInProgress = false;
      updateLockButtons();
    }
  });
});
