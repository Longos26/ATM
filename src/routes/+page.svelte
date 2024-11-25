<script lang="ts">
    import { Button, Input } from 'flowbite-svelte';

    interface TaskDetails {
        id: number;
        name: string;
        status: string;
    }

    interface Transaction {
        type: string;
        amount: number;
        balanceAfter: number;
        timestamp: string;
    }

    let tasks: TaskDetails[] = $state([]);
    let taskName = $state('');
    let taskStatus = $state('');
    let balance = $state(0);
    let pin = '1234';
    let enteredPin = $state('');
    let isAuthenticated = $state(false);
    let transactionHistory: Transaction[] = $state([]);

    let depositAmount = $state(0);
    let withdrawAmount = $state(0);
    let newPin = $state('');
    let confirmPin = $state('');

    let showModal = $state(false);
    let modalMessage = $state('');
    let actionType = $state('');
    let amountInput = $state(0); // New state for input amount

    function openModal(message: string, action: string) {
        modalMessage = message;
        actionType = action;
        amountInput = 0; // Reset input amount when opening modal
        showModal = true;
    }

    function closeModal() {
        showModal = false;
    }

    function confirmAction() {
        closeModal();
        if (actionType === 'deposit') {
            deposit(amountInput);
        } else if (actionType === 'withdraw') {
            withdraw(amountInput);
        } else if (actionType === 'checkBalance') {
            alert(`Current balance: $${balance}`); // Show balance in an alert
        } else if (actionType === 'exit') {
            isAuthenticated = false; // Go back to authentication
            alert("Exiting the application. Please log in again."); // Optional message
        } else if (actionType === 'resetUser  ') {
            resetUser ();
        } else if (actionType === 'changePin') {
            changePin();
        }
    }

    async function deposit(amount: number) {
        if (amount <= 0) {
            alert("Deposit amount must be greater than zero.");
            return;
        }
        if (balance + amount <= 150000) {
            const response = await fetch('http://routes.php/api/addtransaction', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    payload: [{
                        user_id: 1, // Replace with the actual user ID
                        transac_type: 'Deposit',
                        amount: amount,
                        balance_after: balance + amount,
                        timestamp: new Date().toLocaleString(),
                    }]
                })
            });
            const data = await response.json();
            if (data.msg) {
                alert(data.msg);
            }
            balance += amount;
            recordTransaction('Deposit', amount);
        } else {
            alert("Cannot deposit: Maximum balance limit of $150,000 exceeded.");
        }
    }

    async function withdraw(amount: number) {
        if (amount <= 0) {
            alert("Withdrawal amount must be greater than zero.");
            return;
        }
        if (amount <= balance) {
            const response = await fetch('http://your-api-url.com/api/addtransaction', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    payload: [{
                        user_id: 1, // Replace with the actual user ID
                        transac_type: 'Withdrawal',
                        amount: amount,
                        balance_after: balance - amount,
                        timestamp: new Date().toLocaleString(),
                    }]
                })
            });
            const data = await response.json();
            if (data.msg) {
                alert(data.msg);
            }
            balance -= amount;
            recordTransaction('Withdrawal', amount);
        } else {
            alert("Insufficient balance");
        }
    }

    function recordTransaction(type: string, amount: number) {
        transactionHistory.push({
            type,
            amount,
            balanceAfter: balance,
            timestamp: new Date().toLocaleString(),
        });
    }

    function authenticate() {
        if (enteredPin === pin) {
            isAuthenticated = true;
        } else {
            alert("Incorrect PIN");
        }
    }

    function exit() {
        openModal("Are you sure you want to exit?", 'exit');
    }

    function resetUser () {
        openModal("Are you sure you want to reset the user?", 'reset User ');
    }

    function changePin() {
        if (newPin === confirmPin && newPin.length === 4) {
            pin = newPin;
            alert("PIN changed successfully!");
            newPin = '';
            confirmPin = '';
        } else {
            alert("PINs do not match or are invalid.");
        }
    }
</script>

<main class="flex flex-col h-screen p-8 bg-gray-100">
    <div class="max-w-full mx-auto bg-blue-800 shadow-lg rounded-lg p-6 flex-1">
        {#if !isAuthenticated}
        <h2 class="text-xl font-bold mb-4 text-center">BD<span style="color: yellow">O</span></h2>
        <div class="grid grid-cols-1 gap-4 w-full">
            <Input placeholder="Enter your PIN" bind:value={enteredPin} type="password" class ="border-2 border-yellow-500 rounded-lg w-full" />
            <Button color="yellow" class="w-full" onclick={authenticate}>Authenticate</Button>
        </div>
        {:else}
            <h2 class="text-xl font-bold mb-4 text-center">Welcome to BDO ATM</h2>
            <div class="grid grid-cols-2 gap-4 mt-4">
                <Button color="yellow" class="w-full" onclick={() => openModal("Enter deposit amount:", 'deposit')}>Deposit</Button>
            </div>
            <div class="grid grid-cols-2 gap-4 mt-4">
                <Button color="yellow" class="w-full" onclick={() => openModal("Enter withdrawal amount:", 'withdraw')}>Withdraw</Button>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-4">
                <Button color="yellow" class="w-full" onclick={() => openModal("Current balance: $" + balance, 'checkBalance')}>Check Balance</Button>
                <Button color="yellow" class="w-full" onclick={exit}>Exit</Button>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-4">
                <Button color="yellow" class="w-full mt-2" onclick={() => openModal("Are you sure you want to change your PIN?", 'changePin')}>Change PIN</Button>
            </div>

            <div class="mt-4">
                <h3 class="text-lg font-semibold">Change PIN</h3>
                <Input placeholder="New PIN" bind:value={newPin} type="password" class="border-2 border-yellow-500 rounded-lg w-full" />
                <Input placeholder="Confirm New PIN" bind:value={confirmPin} type="password" class="border-2 border-yellow-500 rounded-lg w-full" />
            </div>

            <div class="mt-4">
                <p class="font-bold">Current Balance: $<span class="text-black-600">{balance}</span></p>
            </div>

            <div class="mt-4">
                <h3 class="text-lg font-semibold">Transaction History</h3>
                {#if transactionHistory.length > 0}
                    <ul class="list-disc pl-5">
                        {#each transactionHistory as transaction}
                            <li>{transaction.timestamp}: {transaction.type} of $<span class="font-semibold">{transaction.amount}</span>. Balance after: $<span class="font-semibold">{transaction.balanceAfter}</span></li>
                        {/each}
                    </ul>
                {:else}
                    <p>No transactions yet.</p>
                {/if}
            </div>

            <Button color="red" class="w-full mt-4" onclick={resetUser }>Reset User</Button>
        {/if}
    </div>

    {#if showModal}
    <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h3 class="text-lg font-semibold">{modalMessage}</h3>
            {#if actionType === 'deposit' || actionType === 'withdraw'}
                <Input placeholder="Amount" bind:value={amountInput} type="number" class="border-2 border-yellow-500 rounded-lg w-full mt-4" />
            {/if}
            <div class="mt-4 flex justify-end">
                <Button color="red" class="mr-2" onclick={closeModal}>Cancel</Button>
                <Button color="yellow" onclick={confirmAction}>Confirm</Button>
            </div>
        </div>
    </div>
    {/if}
</main>