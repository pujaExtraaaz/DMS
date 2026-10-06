<?php

namespace App\Support;

class IndianCities
{
    /**
     * Map of states and union territories to their major cities and districts.
     *
     * @var array<string, array<int, string>>
     */
    protected static array $stateCities = [
        'Maharashtra' => [
            'Mumbai', 'Pune', 'Nagpur', 'Nashik', 'Thane', 'Aurangabad', 'Solapur', 'Kolhapur',
            'Amravati', 'Nanded', 'Jalgaon', 'Akola', 'Latur', 'Dhule', 'Ahmednagar', 'Chandrapur',
            'Parbhani', 'Jalna', 'Navi Mumbai', 'Panvel', 'Kalyan', 'Dombivli', 'Vasai-Virar',
            'Mira-Bhayandar', 'Satara', 'Ratnagiri', 'Sangli', 'Wardha', 'Yavatmal', 'Bhandara',
            'Gondia', 'Gadchiroli', 'Sindhudurg', 'Osmanabad', 'Beed', 'Bhiwandi', 'Ulhasnagar',
            'Malegaon', 'Ichalkaranji', 'Baramati', 'Palghar'
        ],
        'Gujarat' => [
            'Ahmedabad', 'Surat', 'Vadodara', 'Rajkot', 'Bhavnagar', 'Jamnagar', 'Junagadh',
            'Gandhinagar', 'Anand', 'Navsari', 'Morbi', 'Bharuch', 'Porbandar', 'Godhra', 'Vapi',
            'Valsad', 'Mehsana', 'Patan', 'Palanpur', 'Surendranagar', 'Bhuj', 'Gandhidham',
            'Veraval', 'Dahod', 'Himmatnagar', 'Amreli', 'Nadiad', 'Ankleshwar'
        ],
        'Karnataka' => [
            'Bengaluru', 'Mysuru', 'Hubballi', 'Dharwad', 'Mangaluru', 'Belagavi', 'Kalaburagi',
            'Davanagere', 'Ballari', 'Vijayapura', 'Shivamogga', 'Tumakuru', 'Raichur', 'Bidar',
            'Hosapete', 'Udupi', 'Hassan', 'Mandya', 'Chikkamagaluru', 'Kolar', 'Chitradurga',
            'Gadag', 'Bagalkote', 'Haveri', 'Yadgir', 'Chamarajanagar', 'Ramanagara'
        ],
        'Tamil Nadu' => [
            'Chennai', 'Coimbatore', 'Madurai', 'Tiruchirappalli', 'Salem', 'Tiruppur', 'Erode',
            'Vellore', 'Thoothukudi', 'Dindigul', 'Thanjavur', 'Ranipet', 'Sivakasi', 'Karur',
            'Udhagamandalam', 'Hosur', 'Nagercoil', 'Kanchipuram', 'Kumarapalayam', 'Karaikkudi',
            'Neyveli', 'Cuddalore', 'Kumbakonam', 'Tiruvannamalai', 'Pollachi', 'Rajapalayam',
            'Pudukkottai', 'Vaniyambadi', 'Ambur', 'Nagapattinam'
        ],
        'Delhi' => [
            'Delhi', 'New Delhi', 'Central Delhi', 'East Delhi', 'North Delhi', 'North East Delhi',
            'North West Delhi', 'South Delhi', 'South East Delhi', 'South West Delhi', 'West Delhi',
            'Shahdara', 'Dwarka', 'Rohini', 'Connaught Place', 'Saket', 'Karol Bagh'
        ],
        'Uttar Pradesh' => [
            'Lucknow', 'Kanpur', 'Ghaziabad', 'Agra', 'Meerut', 'Varanasi', 'Prayagraj', 'Bareilly',
            'Aligarh', 'Moradabad', 'Saharanpur', 'Gorakhpur', 'Noida', 'Greater Noida', 'Firozabad',
            'Jhansi', 'Muzaffarnagar', 'Mathura', 'Ayodhya', 'Rampur', 'Shahjahanpur', 'Farrukhabad',
            'Maunath Bhanjan', 'Hapur', 'Faizabad', 'Etawah', 'Mirzapur', 'Bulandshahr', 'Sambhal',
            'Amroha', 'Hardoi', 'Fatehpur', 'Raebareli', 'Orai', 'Sitapur', 'Bahraich', 'Modinagar',
            'Unnao', 'Jaunpur', 'Lakhimpur', 'Hathras', 'Banda', 'Pilibhit', 'Barabanki'
        ],
        'Rajasthan' => [
            'Jaipur', 'Jodhpur', 'Kota', 'Bikaner', 'Ajmer', 'Udaipur', 'Bhilwara', 'Alwar',
            'Bharatpur', 'Sikar', 'Pali', 'Sri Ganganagar', 'Barmer', 'Chittorgarh', 'Jhunjhunu',
            'Kishangarh', 'Beawar', 'Hanumangarh', 'Dholpur', 'Sawai Madhopur', 'Churu', 'Tonk'
        ],
        'West Bengal' => [
            'Kolkata', 'Howrah', 'Asansol', 'Siliguri', 'Durgapur', 'Bardhaman', 'Malda',
            'Baharampur', 'Habra', 'Kharagpur', 'Shantipur', 'Dankuni', 'Dhulian', 'Ranaghat',
            'Haldia', 'Raiganj', 'Krishnanagar', 'Nabadwip', 'Medinipur', 'Jalpaiguri', 'Balurghat',
            'Basirhat', 'Bankura', 'Darjeeling', 'Alipurduar', 'Purulia'
        ],
        'Madhya Pradesh' => [
            'Bhopal', 'Indore', 'Jabalpur', 'Gwalior', 'Ujjain', 'Sagar', 'Dewas', 'Satna',
            'Ratlam', 'Rewa', 'Katni', 'Singrauli', 'Burhanpur', 'Khandwa', 'Bhind', 'Chhindwara',
            'Guna', 'Shivpuri', 'Vidisha', 'Chhatarpur', 'Damoh', 'Mandsaur', 'Khargone', 'Neemuch',
            'Pithampur', 'Hoshangabad', 'Sehore'
        ],
        'Telangana' => [
            'Hyderabad', 'Warangal', 'Nizamabad', 'Khammam', 'Karimnagar', 'Ramagundam',
            'Mahbubnagar', 'Nalgonda', 'Adilabad', 'Suryapet', 'Siddipet', 'Miryalaguda',
            'Jagtial', 'Mancherial', 'Kothagudem', 'Secunderabad'
        ],
        'Andhra Pradesh' => [
            'Visakhapatnam', 'Vijayawada', 'Guntur', 'Nellore', 'Kurnool', 'Kakinada',
            'Rajamahendravaram', 'Kadapa', 'Mangalagiri', 'Tirupati', 'Anantapur', 'Vizianagaram',
            'Eluru', 'Ongole', 'Nandyal', 'Machilipatnam', 'Adoni', 'Tenali', 'Proddatur',
            'Chittoor', 'Hindupur', 'Bhimavaram', 'Madanapalle', 'Guntakal', 'Srikakulam'
        ],
        'Punjab' => [
            'Ludhiana', 'Amritsar', 'Jalandhar', 'Patiala', 'Bathinda', 'Hoshiarpur', 'Mohali',
            'Batala', 'Pathankot', 'Moga', 'Abohar', 'Malerkotla', 'Khanna', 'Phagwara', 'Muktsar',
            'Barnala', 'Rajpura', 'Firozpur', 'Kapurthala', 'Sangrur', 'Fazilka'
        ],
        'Haryana' => [
            'Faridabad', 'Gurugram', 'Panipat', 'Ambala', 'Yamunanagar', 'Rohtak', 'Hisar',
            'Karnal', 'Sonipat', 'Panchkula', 'Bhiwani', 'Sirsa', 'Bahadurgarh', 'Jind',
            'Thanesar', 'Kaithal', 'Rewari', 'Palwal', 'Hansi', 'Narnaul', 'Fatehabad'
        ],
        'Kerala' => [
            'Thiruvananthapuram', 'Kochi', 'Kozhikode', 'Kollam', 'Thrissur', 'Kannur',
            'Alappuzha', 'Kottayam', 'Palakkad', 'Manjeri', 'Thalassery', 'Ponnani',
            'Vatakara', 'Kanhangad', 'Payyanur', 'Koyilandy', 'Parappanangadi', 'Kasaragod'
        ],
        'Bihar' => [
            'Patna', 'Gaya', 'Bhagalpur', 'Muzaffarpur', 'Purnia', 'Darbhanga', 'Bihar Sharif',
            'Arrah', 'Begusarai', 'Katihar', 'Munger', 'Chhapra', 'Danapur', 'Saharsa', 'Sasaram',
            'Hajipur', 'Dehri', 'Bettiah', 'Motihari', 'Bagaha', 'Siwan', 'Kishanganj', 'Jamalpur',
            'Buxar', 'Jehanabad', 'Aurangabad'
        ],
        'Odisha' => [
            'Bhubaneswar', 'Cuttack', 'Rourkela', 'Berhampur', 'Sambalpur', 'Puri', 'Balasore',
            'Bhadrak', 'Baripada', 'Jharsuguda', 'Bargarh', 'Jeypore', 'Rayagada', 'Dhenkanal',
            'Paradip', 'Angul'
        ],
        'Jharkhand' => [
            'Ranchi', 'Jamshedpur', 'Dhanbad', 'Bokaro Steel City', 'Deoghar', 'Phusro',
            'Hazaribagh', 'Giridih', 'Ramgarh', 'Medininagar', 'Chirkunda', 'Chaibasa'
        ],
        'Assam' => [
            'Guwahati', 'Silchar', 'Dibrugarh', 'Jorhat', 'Nagaon', 'Tinsukia', 'Tezpur',
            'Bongaigaon', 'Dhubri', 'Diphu', 'North Lakhimpur', 'Karimganj', 'Sivasagar', 'Goalpara'
        ],
        'Chhattisgarh' => [
            'Raipur', 'Bhilai', 'Bilaspur', 'Korba', 'Rajnandgaon', 'Jagdalpur', 'Raigarh',
            'Ambikapur', 'Dhamtari', 'Mahasamund', 'Durg'
        ],
        'Uttarakhand' => [
            'Dehradun', 'Haridwar', 'Roorkee', 'Haldwani', 'Rudrapur', 'Kashipur', 'Rishikesh',
            'Pithoragarh', 'Ramnagar', 'Manglaur', 'Nainital', 'Almora'
        ],
        'Goa' => [
            'Panaji', 'Margao', 'Vasco da Gama', 'Mapusa', 'Ponda', 'Bicholim', 'Curchorem',
            'Cuncolim', 'Canacona'
        ],
        'Himachal Pradesh' => [
            'Shimla', 'Dharamshala', 'Solan', 'Mandi', 'Kullu', 'Baddi', 'Nahan', 'Paonta Sahib',
            'Sundarnagar', 'Chamba', 'Una', 'Hamirpur', 'Bilaspur'
        ],
        'Jammu and Kashmir' => [
            'Srinagar', 'Jammu', 'Anantnag', 'Baramulla', 'Sopore', 'Kathua', 'Udhampur',
            'Punch', 'Rajouri', 'Ganderbal', 'Pulwama', 'Kupwara'
        ],
        'Tripura' => ['Agartala', 'Dharmanagar', 'Udaipur', 'Kailashahar', 'Belonia', 'Khowai'],
        'Meghalaya' => ['Shillong', 'Tura', 'Jowai', 'Nongstoin', 'Williamnagar'],
        'Manipur' => ['Imphal', 'Churachandpur', 'Thoubal', 'Bishnupur', 'Kakching', 'Ukhrul'],
        'Nagaland' => ['Kohima', 'Dimapur', 'Mokokchung', 'Tuensang', 'Wokha', 'Zunheboto'],
        'Mizoram' => ['Aizawl', 'Lunglei', 'Champhai', 'Serchhip', 'Kolasib', 'Lawngtlai'],
        'Arunachal Pradesh' => ['Itanagar', 'Naharlagun', 'Pasighat', 'Tawang', 'Ziro', 'Tezu'],
        'Sikkim' => ['Gangtok', 'Namchi', 'Geyzing', 'Mangan', 'Rangpo', 'Singtam'],
        'Chandigarh' => ['Chandigarh'],
        'Puducherry' => ['Puducherry', 'Karaikal', 'Mahe', 'Yanam', 'Ozhukarai'],
        'Andaman and Nicobar Islands' => ['Port Blair'],
        'Ladakh' => ['Leh', 'Kargil'],
        'Dadra and Nagar Haveli and Daman and Diu' => ['Daman', 'Diu', 'Silvassa'],
        'Lakshadweep' => ['Kavaratti', 'Agatti', 'Amini', 'Andrott', 'Minicoy'],
    ];

    /**
     * Get all cities mapped by state.
     */
    public static function all(): array
    {
        return self::$stateCities;
    }

    /**
     * Get cities list for a given state name.
     */
    public static function getCitiesForState(?string $state): array
    {
        if (! $state) {
            return [];
        }

        $normalizedState = self::normalizeStateName($state);
        return self::$stateCities[$normalizedState] ?? [];
    }

    /**
     * Verify whether a city belongs to the given state.
     */
    public static function isValidCityForState(?string $city, ?string $state): bool
    {
        $city = trim((string) $city);
        $state = trim((string) $state);

        if ($city === '' || $state === '') {
            return true;
        }

        $normalizedState = self::normalizeStateName($state);
        $citiesInTargetState = array_map('mb_strtolower', self::$stateCities[$normalizedState] ?? []);

        // If the state has a known list and the city is in it, it's valid
        if (in_array(mb_strtolower($city), $citiesInTargetState, true)) {
            return true;
        }

        // If the city is explicitly known to belong to ANOTHER state, it's invalid
        foreach (self::$stateCities as $otherState => $otherCities) {
            if ($otherState === $normalizedState) {
                continue;
            }

            $lowerOtherCities = array_map('mb_strtolower', $otherCities);
            if (in_array(mb_strtolower($city), $lowerOtherCities, true)) {
                // City definitely belongs to another state!
                return false;
            }
        }

        // If the city is an unlisted locality or town not explicitly belonging to another state, allow it
        return true;
    }

    /**
     * Normalize state name matching IndianStates.
     */
    protected static function normalizeStateName(string $state): string
    {
        $state = trim($state);
        foreach (array_keys(self::$stateCities) as $knownState) {
            if (strcasecmp($state, $knownState) === 0) {
                return $knownState;
            }
        }

        return $state;
    }
}

