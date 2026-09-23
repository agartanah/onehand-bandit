const buttonSpin = document.getElementById("button-spin");
const slots = document.querySelectorAll(".slot img");
const score = document.getElementById("score");
const bet = document.getElementById("bet");

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
    buttonSpin.disabled = true;

    const animations = [];

    for (const slot of slots) {
      animations.push(spinSlot(slot));
    }

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

    await new Promise((resolve) => setTimeout(resolve, 7000));

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

    balance -= Number(bet.value);
    balance += Number(data.win);

    score.textContent = balance;
  } catch (error) {
    console.error("Ошибка:", error);
  } finally {
    localStorage.setItem("balance", balance);
    buttonSpin.disabled = false;
  }
});
