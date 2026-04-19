<!DOCTYPE html>
<html lang = "en">
    <head>
       <meta charset="UTF-8">
       <title =>replica-github login</title>
        <link rel = "stylesheet" href =replica-style.css>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>

    <body>
        <div class = header></div>
            <div id="parent-container">
                
                <main>
                    <div id = "login-container">
                        <div id = "login-header">
                            <span><svg aria-hidden="true" focusable="false" class="octicon octicon-mark-github SessionsAuthHeader-module__avatarCoinGithub__PRcYjxl" viewBox="0 0 24 24" width="48" height="48" fill="currentColor" display="inline-block" overflow="visible" style="vertical-align:text-bottom"><path d="M12 1C5.923 1 1 5.923 1 12c0 4.867 3.149 8.979 7.521 10.436.55.096.756-.233.756-.522 0-.262-.013-1.128-.013-2.049-2.764.509-3.479-.674-3.699-1.292-.124-.317-.66-1.293-1.127-1.554-.385-.207-.936-.715-.014-.729.866-.014 1.485.797 1.691 1.128.99 1.663 2.571 1.196 3.204.907.096-.715.385-1.196.701-1.471-2.448-.275-5.005-1.224-5.005-5.432 0-1.196.426-2.186 1.128-2.956-.111-.275-.496-1.402.11-2.915 0 0 .921-.288 3.024 1.128a10.193 10.193 0 0 1 2.75-.371c.936 0 1.871.123 2.75.371 2.104-1.43 3.025-1.128 3.025-1.128.605 1.513.221 2.64.111 2.915.701.77 1.127 1.747 1.127 2.956 0 4.222-2.571 5.157-5.019 5.432.399.344.743 1.004.743 2.035 0 1.471-.014 2.654-.014 3.025 0 .289.206.632.756.522C19.851 20.979 23 16.854 23 12c0-6.077-4.922-11-11-11Z"></path></svg></span>
                            <span><h1>Sign in to GitHub</h1></span>
                        </div>
                        <div id = "login-content">
                        
                            <div>
                                <label for="email-username-fields" >Username or email address</label>
                                <input type = "text" name = "user-email" id = "email-username-fields" class = "input-text-field">
                            </div>
                            
                           <div>
                            <label for="user-password"> Password<a href ="">Forgot password?</a></label>
                            <input type = "text" name = "user-password" id = "user-password" class = "input-text-field">
                           </div>

                            <input type = "submit" name = "commit" value = "Sign-in" id = "sign-in-button">

                            <div class="spacer">
                                <hr>
                                <span>or</span>
                                <hr>
                            </div>

                        
                            <button id = "continue-with -google" class = "alternative-login-button" type = "submit">
                                <span class = "button-content">
                                    <span id = "google-logo"><img src = "https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg"></span>
                                    <span class = "button-label">Continue with Google</span>
                                </span>
                            </button>

                            <button id = "continue-with-apple" class = "alternative-login-button" type = "submit">
                                <span class = "button-content">
                                    <span id = "apple-logo"><svg width="16" height="16" data-view-component="true">          <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" width="16" height="16" data-view-component="true" class="octicon">
                                        <g clip-path="url(#clip0_3150_11451)">
                                            <path d="M8.08803 4.3535C8.74395 4.3535 9.56615 3.91006 10.0558 3.31881C10.4992 2.78299 10.8226 2.03469 10.8226 1.28639C10.8226 1.18477 10.8133 1.08314 10.7948 1C10.065 1.02771 9.18738 1.48963 8.6608 2.10859C8.24508 2.57975 7.86631 3.31881 7.86631 4.07635C7.86631 4.18721 7.88479 4.29807 7.89402 4.33502C7.94021 4.34426 8.01412 4.3535 8.08803 4.3535ZM5.77846 15.5318C6.67457 15.5318 7.07182 14.9313 8.18965 14.9313C9.32596 14.9313 9.57539 15.5133 10.5731 15.5133C11.5524 15.5133 12.2083 14.608 12.8273 13.7211C13.5201 12.7049 13.8065 11.7072 13.825 11.661C13.7603 11.6425 11.885 10.8757 11.885 8.7232C11.885 6.85707 13.3631 6.01639 13.4462 5.95172C12.467 4.5475 10.9796 4.51055 10.5731 4.51055C9.47377 4.51055 8.57766 5.1757 8.01412 5.1757C7.4044 5.1757 6.60066 4.5475 5.64912 4.5475C3.83842 4.5475 2 6.0441 2 8.87101C2 10.6263 2.68363 12.4832 3.52432 13.6842C4.2449 14.7004 4.87311 15.5318 5.77846 15.5318Z" fill="currentColor"></path>
                                        </g>
                                        <defs>
                                            <clipPath id="clip0_3150_11451">
                                            <rect width="16" height="16"></rect>
                                            </clipPath>
                                        </defs>
                                        </svg>
                                        </svg></span>
                                    <span class = "button-label">Continue with Apple</span>
                                </span>
                            </button>

                        </div><!--end of login content-->
                        <div id ="login-footer">
                            <p id = "create-an-account"> New to GitHub? <a href = ""> Create an account</a>
                            </p>

                            <p id = "passkey-signin"> <a href = ""> Sign in with a passkey</a>
                            </p>
                        </div><!--end of lagin footer-->
                    </div> <!--end of login container-->
                </main>
            </div><!--end of parent container-->
       </div><!--end of super parent-->
        <footer>
            <div id = "footer-div">
                <ul>
                    <li>Terms</li>
                    <li>Privacy</li>
                    <li>Docs</li>
                    <li>Contact GitHub Support</li>
                    <li>Manage cookies</li>
                    <li>Do not share my personal information</li>
                </ul>
            </div>
        </footer>
    </body>
</html>