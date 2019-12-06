from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.by import By
from selenium.common.exceptions import NoSuchElementException
from .base_testcase import BaseTestCase

class TestLibraryAdmin(BaseTestCase):
    """
    Test cases around homework library admin page
    """
    def __init__(self, testname):
        super().__init__(testname, user_id="superuser", user_password="superuser", user_name="Clark")
        # This is just a random repository picked because it wasn't as big as the main Submitty repo
        self.sourceUrl = "https://github.com/Submitty/Tutorial.git"
        self.sourceName = "Tutorial"
        self.manageUrl = "/homework/library/manage"

    # test that a source can be added from github
    def test_add_git_source(self):
        self.get(self.manageUrl)
        # Make sure the source doesn't exist
        self.test_delete_source()
        # Change tabs
        gitTabBtn = self.driver.find_element_by_id("page_2_nav")
        gitTabBtn.click()
        gitUrlInput = self.driver.find_element_by_id("git_url")
        self.assertTrue(gitUrlInput.is_displayed())
        # Submit url
        gitUrlInput.send_keys(self.sourceUrl)
        # Wait because it sometimes isn't ready to click right away
        gitSubmitBtn = WebDriverWait(self.driver, 10).until(EC.element_to_be_clickable((By.CSS_SELECTOR, "form#libraryGitForm input[type='submit']")))
        gitSubmitBtn.click()
        # Wait for success message
        self.assertTrue(self.wait_for_message())

    # Delete the source if it exists
    def test_delete_source(self):
        self.get(self.manageUrl)
        try:
            # source divs
            sources = self.driver.find_elements(By.CSS_SELECTOR, "#library-source-list>div>div")
            # search for the submitty source
            for element in sources:
                if (element.find_element(By.CSS_SELECTOR, "span:first-child").text == self.sourceName):
                    # Click delete button
                    element.find_element(By.CSS_SELECTOR, "span>button:last-child").click()
                    # Click ok on the confirmation popup
                    self.driver.switch_to.alert.accept()
                    # Wait for deletion confirmation
                    self.assertTrue(self.wait_for_message())
                    # Reload. Easier that waiting for the page javascript to redirect
                    self.get(self.manageUrl)
        except:
            # No sources loaded. Ok
            pass

    # Check for a message popup after an ajax request.
    # Returns true on success message, or false on failure message or no message
    def wait_for_message(self):
        self.wait_after_ajax()
        try:
            success_message = self.driver.find_element(By.CSS_SELECTOR, "div#messages div.alert-success")
            return True
        except NoSuchElementException:
            return False

if __name__ == "__main__":
    import unittest
    unittest.main()
