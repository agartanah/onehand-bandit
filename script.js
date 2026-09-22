const buttonSpin = document.getElementById("button-spin");
const slots = document.querySelectorAll(".slot img");
const score = document.getElementById("score");
const bet = document.getElementById("bet");

let balance = Number(localStorage.getItem("balance"));

if (localStorage.getItem("balance") === null) {
  balance = 10000;
  localStorage.setItem("balance", balance);
}

score.textContent = balance;

buttonSpin.addEventListener("click", async () => {
  try {
    buttonSpin.disabled = true;

    if (balance < Number(bet.value) || Number(bet.value) <= 0) {
      return;
    }

    const response = await fetch("spin.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        bet: Number(bet.value),
        size: 3,
      }),
    });

    if (!response.ok) {
      console.error("HTTP ошибка:", response.status);
      return;
    }

    const data = await response.json();

    console.debug("Ответ сервера:", data);

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
