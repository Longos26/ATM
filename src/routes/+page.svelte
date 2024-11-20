<script lang="ts">
    import { Button } from 'flowbite-svelte';
    import { get, writable } from 'svelte/store';

    const amount = writable(0); // Store for the amount input

    async function handleAction(action: string) {
        let response;
        const amountValue = get(amount); // Get the current amount value

        switch (action) {
            case 'Withdraw':
                response = await fetch('/api/withdraw', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ amount: amountValue })
                });
                break;
            case 'Deposit':
                response = await fetch('/api/deposit', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ amount: amountValue })
                });
                break;
            case 'Balance':
                response = await fetch('/api/balance', { method: 'GET' });
                break;
            case 'Exit':
                response = await fetch('/api/exit', { method: 'POST' });
                break;
            default:
                alert('Unknown action');
                return;
        }

        if (response.ok) {
            const data = await response.json();
            alert(`Success: ${data.message}`);
            if (data.balance !== undefined) {
                // Update the balance if available
                console.log(`New Balance: $${data.balance}`);
            }
        } else {
            const errorData = await response.json();
            alert(`Error: ${errorData.message}`);
        }
    }
</script>

<input type="number" bind:value={$amount} placeholder="Enter amount" class="border p-2" />

<Button class="bg-green-500 hover:bg-green-600 text-white" on:click={() => handleAction('Withdraw')}>Withdraw</Button>
<Button class="bg-green-500 hover:bg-green-600 text-white" on:click={() => handleAction('Deposit')}>Deposit</Button>
<Button class="bg-green-500 hover:bg-green-600 text-white" on:click={() => handleAction('Balance')}>Balance</Button>
<Button class="bg-green-500 hover:bg-green-600 text-white" on:click={() => handleAction('Exit')}>Exit</Button>