public function index()
{
    // Initialize an associative array to store data.
    $data['walletBalance'] = getAmount($this->user->balance);
    $data['interestBalance'] = getAmount($this->user->interest_balance);
    
    // Calculate total deposits and payouts for the user.
    $data['totalDeposit'] = getAmount($this->user->funds()->whereNull('plan_id')->whereStatus(1)->sum('amount'));
    $data['totalPayout'] = getAmount($this->user->payout()->whereStatus(2)->sum('amount'));
    
    // Calculate referral bonuses for deposit and investment.
    $data['depositBonus'] = getAmount($this->user->referralBonusLog()->where('type', 'deposit')->sum('amount'));
    $data['investBonus'] = getAmount($this->user->referralBonusLog()->where('type', 'invest')->sum('amount'));
    
    // Get the last referral bonus amount received by the user.
    $data['lastBonus'] = getAmount(optional($this->user->referralBonusLog()->latest()->first())->amount);
    
    // Calculate total interest profit earned by the user.
    $data['totalInterestProfit'] = getAmount($this->user->transaction()->where('balance_type', 'interest_balance')->where('trx_type', '+')->sum('amount'));
    
    // Fetch data related to the user's investment.
    $roi = Investment::where('user_id', $this->user->id)
        ->selectRaw('SUM( amount ) AS totalInvestAmount')
        ->selectRaw('COUNT( id ) AS totalInvest')
        // ...
        ->get()->makeHidden('nextPayment')->toArray();
    $data['roi'] = collect($roi)->collapse();
    
    // Calculate the number of support tickets for the user.
    $data['ticket'] = Ticket::where('user_id', $this->user->id)->count();
    
    // Initialize arrays to store monthly investment, payout, funding, and bonus data.
    $monthlyInvestment = collect(['January' => 0, 'February' => 0, ...]);
    $monthlyPayout = collect(['January' => 0, 'February' => 0, ...]);
    $monthlyFunding = collect(['January' => 0, 'February' => 0, ...]);
    $monthlyReferralInvestBonus = collect(['January' => 0, 'February' => 0, ...]);
    $monthlyReferralFundBonus = collect(['January' => 0, 'February' => 0, ...]);
    
    // Fetch and organize monthly investment data.
    Investment::where('user_id', $this->user->id)
        ->whereBetween('created_at', [
            Carbon::now()->startOfYear(),
            Carbon::now()->endOfYear(),
        ])
        ->select(
            DB::raw('sum(amount) as totalAmount'),
            DB::raw("DATE_FORMAT(created_at,'%M') as months")
        )
        // ...
        ->get()->makeHidden('nextPayment')->map(function ($item) use ($monthlyInvestment) {
            $monthlyInvestment->put($item['months'], round($item['totalAmount'], 2));
        });
    
    // Fetch and organize monthly payout data.
    $this->user->payout()->whereStatus(2)
        ->whereBetween('created_at', [
            Carbon::now()->startOfYear(),
            Carbon::now()->endOfYear(),
        ])
        ->select(
            DB::raw('sum(amount) as totalAmount'),
            DB::raw("DATE_FORMAT(created_at,'%M') as months")
        )
        // ...
        ->get()->map(function ($item) use ($monthlyPayout) {
            $monthlyPayout->put($item['months'], round($item['totalAmount'], 2));
        });
    
    // ... Similarly, fetch and organize monthly funding, referral invest bonus, and referral fund bonus data.
    
    // Fetch the latest registered user who referred to the current user.
    $latestRegisteredUser = User::where('referral_id', $this->user->id)->latest()->first();
    
    // Return the view with the collected data.
    return view($this->theme . 'user.dashboard', $data, compact('monthly', 'latestRegisteredUser'));
}


i want to do this change before printing interest balance it should check the condition if perday return + refrel value is greater than actual invested money * actual return % then only actual return value should show and aslo plan should completed
please make this fruitfull

Certainly, I understand your requirement. You want to make sure that before printing the interest balance, the system checks if the sum of per day return and referral value is greater than the actual invested money multiplied by the actual return percentage. If this condition is met, then the actual return value should be displayed, and the plan should be marked as completed.

// Calculate the expected return based on actual investment and return percentage
$expectedReturn = $actualInvestment * ($actualReturnPercentage / 100);

// Calculate the sum of per day return and referral value
$totalReturn = $perDayReturn + $referralValue;

// Check if the total return is greater than the expected return
if ($totalReturn >= $expectedReturn) {
    // Set the plan as completed (you need to update your database accordingly)
    $plan->completed = true;

    // Display the actual return value
    $interestBalance = $actualReturn;
} else {
    // Display the regular interest balance calculation
    $interestBalance = $perDayReturn * $numberOfDays;
}
--------------------------------------------------------------
public function index()
{
    // ... (existing code)

    $totalInterestProfit = $this->user->transaction()
        ->where('balance_type', 'interest_balance')
        ->where('trx_type', '+')
        ->sum('amount');

    // Placeholder method for calculating expected interest profit
    $expectedInterestProfit = $this->calculateExpectedInterestProfit($this->user);

    // Placeholder method for calculating sum of per day return and referral value
    $sumPerDayAndReferral = $this->calculateSumPerDayAndReferral($this->user);

    if ($sumPerDayAndReferral >= $expectedInterestProfit) {
        $totalInterestProfit = $expectedInterestProfit;
    }

    $data['totalInterestProfit'] = getAmount($totalInterestProfit);

    // ... (rest of the code)
}

// Placeholder method for calculating expected interest profit
private function calculateExpectedInterestProfit($user)
{
    // Replace with your logic to calculate expected interest profit
    // For example: return $user->investmentAmount * $user->interestRate;
    return 0;
}

// Placeholder method for calculating sum of per day return and referral value
private function calculateSumPerDayAndReferral($user)
{
    // Replace with your logic to calculate sum of per day return and referral value
    // For example: return $user->perDayReturn + $user->referralValue;
    return 0;
}
