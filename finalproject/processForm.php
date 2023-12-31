<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // TODO: get city and coordinates from form and lillian's results
    $city = 'London';
    $coordinates = [
        'lat' => 51.50853,
        'lon' => -0.12574
    ];

    if (isset($_POST['maxDist']) && !empty($_POST['maxDist'])) {
        $distanceLimit = $_POST['maxDist'];
    } else {
        $distanceLimit = 6.5;
    }

    $wheelchair = isset($_POST['accessibility']) ? $_POST['accessibility'] : '';
    $wifi = isset($_POST['wifi']) ? $_POST['wifi'] : '';

    // get the array of activity categories
    $catsWanted = [];
    if (isset($_POST["activity"])) {
        foreach ($_POST["activity"] as $activityCat) {
            $catsWanted[] = $activityCat;
        }
    }

    // if food category is selected, also check if user provided dietary restrictions
    $diet = [];
    if (in_array("catering", $catsWanted)) {
        if (isset($_POST["diet"])) {
            foreach ($_POST["diet"] as $dietType) {
                $diet[] = $dietType;
            }
        }
    }
} else {
    // redirect if accessed directly without submitting the form
    header('Location: form.html');
    exit();
}
?>

<!doctype html>
<html>
<head>
    <title>Your results</title>
    <meta charset="utf-8" />
    <script src="https://code.jquery.com/jquery-3.7.1.slim.js" integrity="sha256-UgvvN8vBkgO0luPSUl2s8TIlOSYRoGFAX4jlCIm9Adc=" crossorigin="anonymous"></script>
    <style type="text/css">
        body {
            width: 100%;
            background-color: white;
            font-family:'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
        }
        h1 {
            margin-top: 50px;
            color: black;
            text-align: center;
        }
        h2 {
            margin: 10px 0px;
            color: grey;
            text-align: center;
            width: 100%;
        }
        .input-err {
            display: block;
            font-size: 18px;
            color: black;
        }
        #cats-container {
            display: block;
            width: 100%;
            margin: 0 auto;
            padding: 0px;
        }
        .catResults {
            width: 100%;
            padding: 10px 0px 30px 0px;
            margin: 0px;
            display: none; /* not shown unless user chose the category in the form */
        }
        .catResults input[type='button'] {
            padding: 10px; /* Adjust the padding as needed */
            display: inline-block;
            border-radius: 0; 
            border: 1px solid grey;
            background-color: white;
        }
        .catResults input[type='button']:hover {
            cursor: pointer;
        }

        .panes {
            display: flex;
            flex-wrap: wrap;
            font-size: 20px;
            width: 100%;
            margin: 0 auto;
            overflow: scroll;
            overflow-x: hidden;
            height: 400px;
        }
        #catering-container {
            background-color: #ffdbad;
        }
        #commercial-container {
            background-color: #e3c7cc;
        }
        #natural-container {
            background-color:#CED097;
        }
        #cultural-container {
            background-color: #E5BC9F;
        }
        #entertain-container {
            background-color: #bbd8b3ff;
        }
        form {
            display: block;
            margin-bottom: 10px;
        }

        .place-info {
            text-align: left;
            display: block;
            font-size: 18px;
            padding: 10px;
            line-height: 1.5em;
            margin: 6px;
            width: 300px;
            display: block;
            background-color: white;
            pointer-events: all;
        }

        a {
            text-decoration: none;
        }
        a:hover {
            cursor: pointer;
            color: #b6a75e;
        }
        ul {
            line-height: 1.8em;
            padding-left: 0px;
        }
        ul li {
            margin: 20px 0px;
            list-style: none;
            line-height: 1.3em;
        }
        .place-name {
            font-weight: bold;
        }
        b {
            font-family: 'Courier New', Courier, monospace;
        }

        /* tag icon with text for labeling results */
        .tag-container {
            position: relative;
            display: inline-block;
        }

        .tag-container img {
            width: 100px;
        }

        .tag-container .tag-text {
            position: absolute;
            top: 40%;
            left: 60%;
            transform: translate(-50%, -50%);
            color: black;
            font-size: 16px;
            text-align: center;
        }
    </style>
    <script>
        const cateringTypes = [
            "catering.restaurant",
            "catering.cafe", 
            "catering.bar"
        ];

        const cateringTags = {
            "catering.restaurant": 'Restaurant',
            "catering.cafe": 'Cafe', 
            "catering.bar": 'Bar',
            "vegetarian": "Vegetarian",
            "vegan": "Vegan", 
            "halal": "Halal", 
            "kosher": "Kosher"
        };

        const commercialTags = {
            "commercial.supermarket": "Supermarket",
            "commercial.marketplace": "Marketplace",
            "commercial.shopping_mall": "Mall",
            "commercial.clothing": "Clothing",
            // "service.financial.bank%2Cservice.financial.atm": "Banks/ATM",
            // "public_transport": "Transport",
        };

        const naturalTags = {
            "natural.forest": "Forest",
            "natural.water": "Water",
            "natural.mountain": "Mountain",
            "camping": "Camping Site",
            "leisure.park": "Park",
            // "natural": "Any nature"
        };

        const culturalTags = {
            "tourism.sights": "Tourist",
            "entertainment.culture": "Theatre/Art",
            "entertainment.museum": "Museum",
            "tourism.sights.place_of_worship": "Religious",
            "building.historic": "Historical"
        };

        // for extracting info from result places
        const basicFields = [
            "year_of_construction",
            "opening_hours",
            "phone",
            "website",
        ];

        const cityName = <?php echo json_encode($city); ?>;
        const coordinates = <?php echo json_encode($coordinates); ?>;
        const distMeters = <?php echo json_encode($distanceLimit * 1000); ?>;
        const wheelchair = <?php echo json_encode($wheelchair); ?>;   // empty string means false
        const wifi = <?php echo json_encode($wifi); ?>;               // empty string means false
        const dietRestrictions = <?php echo json_encode($diet); ?>;
        const allCategories = <?php echo json_encode($catsWanted); ?>;

        console.log("meters: " + distMeters);
        console.log("city:" + cityName);
        console.log("wheelchair: " + wheelchair + ", wifi: " + wifi);

        // FUNCTION DEFINITIONS
        function getCatTags(containerId) {
            switch (containerId) {
                case "catering-container":
                    return cateringTags;
                case "commercial-container":
                    return commercialTags;
                case "natural-container":
                    return naturalTags;
                case "cultural-container":
                    return culturalTags;
                default:
                    return {};
            }
        }

        function makeTag(text) {
            text = text.replace(/_/g, ' ');
            capitalized = text[0].toUpperCase() + text.slice(1);

            const html = `<div class="tag-container">
                            <img src="tag-icon.png" alt="grey tag with text">
                            <div class="tag-text">${capitalized}</div>
                            </div>`;

            return html;
        }

        function makeLink(classname, url, text) {
            return `<a href="${url}" class="${classname}">${text}</a>`;
        }

        function makeChkBox(name, id, val) {
            return `<input type="checkbox" name="${name}" id="${id}" value="${val}" />`;
        }

        function makeFilter(classname, id, val) {
            return `<input type="button" value="${val}" class="${classname}" id="${id}" />`;
        }

        function formatWebsite(url) {
            return makeLink("weblink", url, "🔗 Read more");
        }

        function formatPhone(num) {
            return makeLink("phonelink", "tel:" + num, "📞 Call us"); 
        }

        function formatHours(hoursStr) {
            let formatted = hoursStr.replace(/;/g, '<br />&nbsp;&nbsp;&nbsp;&nbsp;');
            formatted = formatted.replace(/, /g, '<br />&nbsp;&nbsp;&nbsp;&nbsp;');
            return "🕒 " + formatted;
        }

        function formatAddress(addressStr) {
            const addressLower = addressStr.toLowerCase();
            const cityLower = cityName.toLowerCase();
            const cityIdx = addressLower.indexOf(cityLower);

            if (cityIdx !== -1) {
                // truncate the address up to the city
                return "📍 " + addressStr.substring(0, cityIdx + cityLower.length);
            } else {
                // if the city is not found, return the full address
                return "📍 " + addressStr;
            }
        }

        function metersToKm(meters) {
            const km = meters / 1000;
            const rounded = Math.round(km * 100) / 100;
            return rounded;
        }

        function formatbasicFields(type, val) {
            switch(type) {
                case "phone":
                    return formatPhone(val);
                case "website": 
                    return formatWebsite(val);
                case "opening_hours":
                    return formatHours(val);
                case "year_of_construction":
                    return "Year built: " + val;
                default: 
                    return "";
            }
        }
    </script>
</head>

<body>
    <h1>PICK YOUR ACTIVITIES</h1>
    <div id="cats-container">
        <div class='catResults' id='catering-container'>
            <h2>Food</h2>
            <form id="catering-form" action="#" method="#">Filter results: </form>
            <div class="panes"></div>
        </div>

        <div class='catResults' id='commercial-container'>
            <h2>Commercial</h2>
            <form id="commercial-form" action="#" method="#">Filter results: </form>
            <div class="panes"></div>
        </div>

        <div class='catResults' id='cultural-container'>
            <h2>Tourism</h2>
            <form id="cultural-form" action="#" method="#">Filter results: </form>
            <div class="panes"></div>
        </div>

        <div class='catResults' id='natural-container'>
            <h2>Nature</h2>
            <form id="natural-form" action="#" method="#">Filter results: </form>
            <div class="panes"></div>
        </div>
        
        <!-- <form id="entertain-form" action="#" method="#">
            <h4>More Fun</h4>
        </form>
        <div class='catResults' id='entertain-container'>
            <h2>Other Entertainment</h2>
            <div class="panes"></div>
        </div> -->
    </div>
    <script>
        async function loadResults(container, categories, conditions) {
            const containerId = `#${container}-container`;
            $(`${containerId} .panes`).html("Searching...");

            if (wheelchair !== "") {
                conditions.push(wheelchair);
            }
            if (wifi !== "") {
                conditions.push(wifi);
            }
            console.log("conditions:");
            console.log(conditions);

            const conditionStr = conditions.length == 0 ? "" : `&conditions=${ conditions.join("%2C") }`;
            const catStr = categories.join("%2C");
            
            // a lot of nature entries don't have enough detail - no guarantee, but we can get some extra entries
            // const maxResults = containerId == "natural-container" ? 30 : 4;
            const maxResults = 10;
            const url = `https://api.geoapify.com/v2/places?categories=${catStr}${conditionStr}&filter=circle%3A${coordinates.lon}%2C${coordinates.lat}%2C${distMeters}&bias=proximity%3A${coordinates.lon}%2C${coordinates.lat}&limit=${maxResults}&apiKey=0b813d154863412cb86acd4b37d93c3b`;

            console.log(url);

            fetch(url)
                .then(res => res.text())
                .then(data => 
                {
                    const locations = JSON.parse(data);
                    locationsArr = locations.features;

                    let dataHTML = "";
                    for (const place of locationsArr) {
                        console.log(place);
                        const info = place.properties;
                        const placeCats = info.categories;

                        // missing key info
                        if (!("name" in info) || !("distance" in info)) {
                            continue;
                        }

                        // features of this place to display as tags
                        const allTags = getCatTags(`${container}-container`);
                        let matchingTags = [];

                        for (const key in allTags) {                            
                            if (placeCats.includes(key)) {
                                matchingTags.push(allTags[key]);
                            }
                        }

                        const tagsHTML = matchingTags.map(makeTag).join('');
                        let list = `<ul>
                            <li class='tags'>${tagsHTML}</li>
                            <li class='place-name'>${info.name}</li>
                            <li>${metersToKm(info.distance)} km away</li>
                            <li>${formatAddress(info.formatted)}</li>`; // this is the full address

                        const moreinfo = info.datasource.raw;

                        for (const key of basicFields) {
                            if (moreinfo.hasOwnProperty(key)) {
                                list += `<li>${formatbasicFields(key, moreinfo[key])}</li>`;
                            }
                        }
                        
                        list += "</ul>";
                        dataHTML += "<div class='place-info'>" + list + "</div>";
                    }

                    // no results found
                    if (dataHTML === "") {
                        dataHTML = "We couldn't find any matching results. You may need to unselect wheelchair only or Wi-Fi only.";
                    }

                    $(`${containerId} .panes`).html(dataHTML); 
                })
            .catch (error => console.log(error));
        }

        function makeCategoryFilters(filterNames, category) {
            const formId = `#${category}-form`;
            const containerId = `#${category}-container`;

            filterNames.forEach((k, idx) => {
                const filter = makeFilter(`${category}Chk`, `chk${idx}${category}`, k);
                $(formId).append(filter);
            });

            $(`${formId} input[type='button']`).each(function() {
                makeClickEvent($(this), $(containerId), $(formId));
            });
        }

        function loadCategory(category, tags, tagDisplayNames, conditions) {
            $(`#${category}-container`).css("display", "block");
            // create filters
            makeCategoryFilters(tagDisplayNames, category);

            // make API call to populate this category's results
            loadResults(category, tags, conditions);
        }

        function makeClickEvent(button, catContainer, catForm) {
            button.click(function(event) {
                event.preventDefault();

                button.toggleClass('on');
                button.css('background-color', button.hasClass('on') ? 'beige' : 'white');

                // check which categories have been selected
                const categories = [];
                catForm.find("input[type='button']").each(function() {
                    if ($(this).hasClass('on')) {
                        const selectedFilter = $(this).val();
                        categories.push(selectedFilter);
                    }
                });

                // display only results that match at least one of the selected categories. 
                // If no filters are selected, show everything
                if (categories.length == 0) {
                    catContainer.find('.place-info').each(function() {
                        $(this).show();
                    })
                } else {
                    catContainer.find('.place-info').each(function() {
                        const tagDivs = $(this).find('.tag-text');

                        // Check if any tag-text div has text that is in the categories array
                        let hasMatchingCategory = false;
                        
                        tagDivs.each(function() {
                            const tagContent = $(this).text();
                            if (categories.includes(tagContent)) {
                                hasMatchingCategory = true;
                                return false; // break out of the loop since we found a match
                            }
                        });

                        // If this place does not have a matching category, hide it
                        if (hasMatchingCategory) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                }
            });
        }

        $(document).ready(function() {
            // food
            if (allCategories.includes("catering")) {
                console.log(dietRestrictions);
                loadCategory("catering", cateringTypes, Object.values(cateringTags), dietRestrictions);
            }
            
            // commercial
            if (allCategories.includes("commercial")) {
                loadCategory("commercial", Object.keys(commercialTags), Object.values(commercialTags), []);
            }

            // natural
            if (allCategories.includes("natural")) {
                loadCategory("natural", Object.keys(naturalTags), Object.values(naturalTags), []);
            }

            // tourist
            if (allCategories.includes("cultural")) {
                loadCategory("cultural", Object.keys(culturalTags), Object.values(culturalTags), []);
            }
        });
    </script>
</body>
</html>
<!-- 
    https://dev.opentripmap.org/docs#/Objects%20list/getListOfPlacesBySuggestions
    categories: https://dev.opentripmap.org/catalog 
    or in json: https://dev.opentripmap.org/en/catalog.tree.json 
 -->
