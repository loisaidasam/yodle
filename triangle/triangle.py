def next_level(triangle, row, column, sum):
  print row, column, sum
	sum += triangle[row][column]
	if row == len(triangle) - 1:
		return sum
	a = next_level(triangle, row+1, column, sum)
	b = next_level(triangle, row+1, column+1, sum)
	if a > b:
		return a
	else:
		return b

f = open('triangle.txt', 'rb')

data = []

for row in f:
	row_data = []
	for i in row.split():
		row_data.append(int(i))
	data.append(row_data)

print next_level(data, 0, 0, 0)

