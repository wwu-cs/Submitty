import os
from unittest import skipIf

from .base_testcase import BaseTestCase
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.common.action_chains import ActionChains
from selenium.webdriver.common.by import By
from selenium.common.exceptions import TimeoutException

CURRENT_PATH = os.path.dirname(os.path.realpath(__file__))

class TestMovedAutoGradeableQuery(BaseTestCase):
    def __init__(self, testname):
        super().__init__(testnames, user_id= "instructor", user_password= "instructor")
        
	def setup_test_start(self, gradeable_category="open", gradeable_id="open_homework", button_name="submit", loaded_selector=(By.XPATH, "//h1[1][normalize-space(text())='New submission for: Open Homework']")):
        self.log_in()
		self.get(url = "/f19/blank/gradeable/QT2")

if __name__ == "__main__":
    import unittest
    unittest.main()

