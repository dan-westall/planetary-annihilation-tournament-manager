<?php if(get_tournament_type($tournament_id) == 'clanwars') : ?>

    <script type="application/javascript">

        var clansListing = <?php echo json_encode(clans::get_clans_listing()); ?>;

    </script>

<?php endif ?>

<script type="text/ng-template" id="error-messages">
    <div ng-message="required">You left the field blank</div>
    <div ng-message="minlength">Your field is too short</div>
    <div ng-message="maxlength">Your field is too long</div>
    <div ng-message="email">Your email address invalid</div>
</script>

<script type="text/ng-template" id="/auto-suggest-clan.html">
    <div class="angucomplete-holder" ng-class="{'angucomplete-dropdown-visible': showDropdown}">
        <input ng-model="searchStr"
               ng-disabled="disableInput"
               type="text"
               placeholder="{{placeholder}}ddd"
               ng-focus="onFocusHandler()"
               class="{{inputClass}}"
               ng-focus="resetHideResults()"
               ng-blur="hideResults($event)"
               autocapitalize="off"
               autocorrect="off"
               autocomplete="off"
               ng-change="inputChangeHandler(searchStr)"/>
        <div class="angucomplete-dropdown" ng-show="showDropdown">
            <div class="angucomplete-searching" ng-show="searching" ng-bind="textSearching"></div>
            <div class="angucomplete-searching" ng-show="!searching && (!results || results.length == 0)" ng-bind="textNoResults"></div>
            <div class="angucomplete-row" ng-repeat="result in results" ng-click="selectResult(result)" ng-mouseenter="hoverRow($index)" ng-class="{'angucomplete-selected-row': $index == currentIndex}">
                <div ng-if="imageField" class="angucomplete-image-holder">
                    <img ng-if="result.image && result.image != ''" ng-src="{{result.image}}" class="angucomplete-image"/>
                    <div ng-if="!result.image && result.image != ''" class="angucomplete-image-default"></div>
                </div>
                <div class="angucomplete-title" ng-if="matchClass" ng-bind-html="result.title"></div>
                <div class="angucomplete-title" ng-if="!matchClass">{{ result.title }}</div>
                <div ng-if="matchClass && result.description && result.description != ''" class="angucomplete-description" ng-bind-html="result.description"></div>
                <div ng-if="!matchClass && result.description && result.description != ''" class="angucomplete-description">{{result.description}}</div>
            </div>
            <div class="angucomplete-row" ng-click="selectResult({title: searchStr, originalObject: { name: searchStr, custom: true }})" ng-mouseenter="hoverRow(results.length)" ng-class="{'angucomplete-selected-row': results.length == currentIndex}">
                <div class="angucomplete-title">Select custom clan name '{{ searchStr }}'</div>
            </div>
        </div>
    </div>
</script>

<script type="text/ng-template" id="signupform.html">

    <div ng-controller="signupFormController">

        <div ng-show="result.message" class="form-message" ng-class="{ '__error': result.type == 'error', '__validation': result.type == 'validation', '__success': result.type == 'success' }">

            <div ng-bind-html="result.message"></div>

        </div>

        <form class="tournament-signup-form" name="playerSignupForm" ng-class="{ 'submission-in-progress': submission }" ng-submit="submitted = true; submitSignup( signupData, playerSignupForm.$valid )" novalidate>

            <div id="in-game-name" class="form-group" ng-class="{ 'has-error' : inGameName }">
                <label for="inGameName">In game Name</label>
                <input type="text" name="inGameName" ng-model="signupData.inGameName" class="form-control" placeholder="In game name" value="<?php echo $ign; ?>" ng-minlength="2" required>
                <div class="ng-message" ng-class="{'__highlight': submitted == true}" ng-messages="playerSignupForm.inGameName.$error" ng-messages-include="error-messages" ng-if="submitted || playerSignupForm.inGameName.$touched">
                    <div ng-message="required">You left your in game name blank.</div>
                </div>
                <div class="description">Please ensure this matches exactly, including the type of brackets used. You will be able to modify this later if it changes.</div>
            </div>

            <div id="email" class="form-group" ng-class="{ 'has-error' : email }">
                <label for="email">Email Address</label>
                <input type="email" name="email" ng-model="signupData.email" class="form-control" placeholder="Email Address" value="<?php echo $email; ?>" required>
                <div class="ng-message" ng-class="{'__highlight': submitted == true}" ng-messages="playerSignupForm.email.$error" ng-messages-include="error-messages" ng-if="submitted || playerSignupForm.email.$touched">
                    <div ng-message="required">You left your email blank.</div>
                </div>
                <div class="description">This e-mail address will be used solely by eXodus, it will not be passed to any third parties. Please ensure this is a monitored e-mail address as we will use it to communicate with you.</div>
            </div>

            <?php if(get_tournament_type($tournament_id) == 'teamarmies') : ?>

                <div id="team-name" class="form-group" ng-class="{ 'has-error' : teamName }">
                    <label for="teamName">Team Name</label>
                    <input type="text" name="teamName" ng-model="signupData.teamName" class="form-control" placeholder="Team name" required>
                    <div class="ng-message" ng-class="{'__highlight': submitted == true}" ng-messages="playerSignupForm.teamName.$error" ng-messages-include="error-messages" ng-if="submitted || playerSignupForm.teamName.$touched"></div>
                </div>

            <?php endif; ?>

            <?php if(get_tournament_type($tournament_id) == 'clanwars') : ?>

                <div class="row">
                    <div class="col-md-6">

                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div id="clan-name" class="form-group" ng-class="{ 'has-error' : clanName }">
                            <label for="clanName">Clan Name</label>

                            <angucomplete-alt id="ex1"
                                              placeholder="Clan name"
                                              pause="100"
                                              local-data="clanList"
                                              selected-object="setClan"
                                              search-fields="clan_name"
                                              title-field="clan_name"
                                              minlength="3"
                                              input-class="form-control"
                                              template-url="/auto-suggest-clan.html"/>
                            <div class="ng-message" ng-class="{'__highlight': submitted == true}" ng-messages="playerSignupForm.clanName.$error" ng-messages-include="error-messages" ng-if="submitted || playerSignupForm.clanName.$touched"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div id="clan-tag" class="form-group" ng-class="{ 'has-error' : clanTag }">
                            <label for="clanTag">Clan Tag</label>
                            <input type="text" name="clanTag" ng-model="signupData.clanTag" class="form-control" placeholder="Clan tag" required>
                            <div class="ng-message" ng-class="{'__highlight': submitted == true}" ng-messages="playerSignupForm.clanTag.$error" ng-messages-include="error-messages" ng-if="submitted || playerSignupForm.clanTag.$touched"></div>
                        </div>
                    </div>
                </div>
                <div id="clan-contact" class="form-group" ng-class="{ 'has-error' : clanContact }">
                    <label for="clanContact">I am clan contact</label><br />
                    <div class="custom-checkbox-style">
                        <input type="checkbox" value="false" id="clanContact" name="clan-contact"  ng-model="signupData.clanContact"/>
                        <label for="clanContact"></label>
                    </div>
                    <label for="clanContact" class="description">When dealing with clans it's easier for everyone if there is just one point of contact</label>
                </div>

            <?php endif; ?>

            <div id="other-details" class="form-group">
                <label>Is there anything else we need to know?</label>
                <textarea ng-model="signupData.otherDetails"></textarea>
            </div>

            <div id="communication-option" class="form-group">
                <label for="communication">Future Communication</label><br />
                <div class="custom-checkbox-style">
                    <input type="checkbox" value="false" id="communication" name="communication"  ng-model="signupData.communication"/>
                    <label for="communication"></label>
                </div>
                <label for="communication" class="description" ng-class="{ 'happy': signupData.communication }">I agree to receive emails from eXodus eSports regarding new products, services or upcoming events. Collected information will not be shared with any third party.<span></span></label>
            </div>

            <input type="submit" value="Join this tournament" class="tournament-btn __signup"/>
            <br />

        </form>

    </div>

</script>
